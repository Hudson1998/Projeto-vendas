<?php

namespace App\Pages;

use App\Interfaces\Pagamento;
use App\Models\Order;
use App\Support\GatewayDePagamentoSimulado;
use App\Support\RegistroDeCompra;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PaginaPagamento implements Pagamento
{
    public function pix(Order $order): Order
    {
        $order->update([
            'forma_pagamento' => 'pix',
            'status_pagamento' => 'pendente',
        ]);

        return $order;
    }

    public function cartao(Order $order): Order
    {
        $order->update([
            'forma_pagamento' => 'cartao',
            'status_pagamento' => 'aprovado',
        ]);

        return $order;
    }

    public function boleto(Order $order): Order
    {
        $order->update([
            'forma_pagamento' => 'boleto',
            'status_pagamento' => 'pendente',
        ]);

        return $order;
    }

    /**
     * Passo 2 do historico: emite a cobranca e registra o JSON do pagamento.
     *
     * O status do pedido passa a vir da resposta do gateway, nao mais de um
     * valor fixo por forma de pagamento -- e o gateway que decide se aprovou.
     *
     * SIMULACAO: GatewayDePagamentoSimulado sai quando a adquirente entrar.
     * Ver o cabecalho daquele arquivo para o passo a passo da troca.
     *
     * @return array<string, mixed> a resposta do gateway
     */
    public function cobrar(Order $order, string $forma): array
    {
        $valorTotal = (float) $order->total + (float) ($order->valor_frete ?? 0);

        $cobranca = GatewayDePagamentoSimulado::cobrar($order, $forma, $valorTotal);

        $order->update([
            'forma_pagamento' => $cobranca['forma'],
            'status_pagamento' => $cobranca['status'],
            'codigo_pagamento' => $cobranca['codigo'],
        ]);

        RegistroDeCompra::registrarPagamento($order->refresh(), $cobranca);

        $this->gerarRelatorioLog($order, 'cobranca_emitida');

        return $cobranca;
    }

    /**
     * A cobranca emitida no passo 2, relida do JSON do historico.
     *
     * O instrumento (payload do pix, digitos do boleto) e grande demais para
     * merecer coluna nova em orders, e ja esta gravado em 2-pagamento.json.
     * A tela de pagamento le dali; se o arquivo nao existir -- disco fora do
     * ar na hora da compra --, reemite a cobranca com o mesmo codigo, que e
     * deterministico o bastante para o cliente nao ver diferenca.
     *
     * @return array<string, mixed>|null
     */
    public function cobrancaEmitida(Order $order): ?array
    {
        if (blank($order->forma_pagamento)) {
            return null;
        }

        $passos = RegistroDeCompra::passosRegistrados($order);
        $cobranca = $passos[RegistroDeCompra::PASSO_PAGAMENTO]['gateway'] ?? null;

        if (is_array($cobranca) && $this->instrumentoCompleto($cobranca, $order->forma_pagamento)) {
            return $cobranca;
        }

        $valorTotal = (float) $order->total + (float) ($order->valor_frete ?? 0);

        return GatewayDePagamentoSimulado::cobrar($order, $order->forma_pagamento, $valorTotal);
    }

    /**
     * O JSON gravado tem tudo que a tela precisa desenhar?
     *
     * Nao basta ter o codigo da cobranca: pix sem payload nao vira QR e boleto
     * sem os 44 digitos nao vira codigo de barras. Faltando qualquer um deles a
     * cobranca e reemitida, o que tambem cobre os pedidos gravados antes de o
     * instrumento existir.
     *
     * @param  array<string, mixed>  $cobranca
     */
    private function instrumentoCompleto(array $cobranca, string $forma): bool
    {
        if (blank($cobranca['codigo'] ?? null)) {
            return false;
        }

        return match ($forma) {
            'pix' => filled($cobranca['instrucao'] ?? null),
            'boleto' => filled($cobranca['instrucao'] ?? null) && filled($cobranca['codigo_barras'] ?? null),
            default => true,
        };
    }

    /**
     * Autoriza a cobranca no cartao com os dados que o cliente digitou.
     *
     * Aprovado vai direto para "aprovado": cartao liquida na hora, sem fila de
     * analise. Recusado nao mexe no status -- o pedido continua pendente e o
     * cliente pode tentar de novo na mesma tela.
     *
     * SIMULACAO: quem decide e o GatewayDePagamentoSimulado.
     *
     * @param  array<string, mixed>  $dadosCartao
     * @return array<string, mixed>
     */
    public function autorizarCartao(Order $order, array $dadosCartao): array
    {
        $autorizacao = GatewayDePagamentoSimulado::autorizarCartao($order, $dadosCartao);

        if ($autorizacao['aprovado']) {
            $order->update(['status_pagamento' => 'aprovado']);
        }

        $this->gerarRelatorioLog($order, $autorizacao['aprovado'] ? 'cartao_autorizado' : 'cartao_recusado');

        return $autorizacao;
    }

    /**
     * Registra que o cliente informou ter pago o pix ou o boleto.
     *
     * Vai para a fila de analise, nao para aprovado: quem confirma credito de
     * pix e boleto e o banco, e ate a confirmacao chegar o pedido fica com
     * alguem para conferir.
     *
     * @return array<string, mixed>
     */
    public function confirmarRecebimento(Order $order, array $cobranca = []): array
    {
        $recibo = GatewayDePagamentoSimulado::confirmarRecebimento($order, $cobranca);

        if ($recibo['recebido']) {
            $this->enviarDocParaAnalise($order);
        }

        $this->gerarRelatorioLog($order, $recibo['recebido'] ? 'recebimento_confirmado' : 'recebimento_nao_identificado');

        return $recibo;
    }

    /**
     * Reemite a cobranca quando a anterior expirou.
     *
     * Codigo novo, janela nova: o pix antigo ja nao serve, e deixar o cliente
     * pagando um QR vencido seria pior do que pedir para gerar outro.
     *
     * @return array<string, mixed>
     */
    public function renovarCobranca(Order $order): array
    {
        $this->gerarRelatorioLog($order, 'cobranca_expirada_renovada');

        return $this->cobrar($order, $order->forma_pagamento);
    }

    public function autenticarTransferencia(Order $order): bool
    {
        $loja = $order->items()->with('product.loja')->first()?->product?->loja;

        return $loja !== null && (new PaginaLoja)->viabilizarTransferencia($loja);
    }

    public function confirmarDados(Order $order): bool
    {
        if ($order->forma_pagamento === 'cartao') {
            return $order->status_pagamento === 'aprovado';
        }

        return filled($order->comprovante_pagamento_path);
    }

    public function validarSeguranca(Order $order, string $ip): bool
    {
        if (blank($ip)) {
            return false;
        }

        $chave = 'seguranca-pagamento:'.$order->user_id;

        if (RateLimiter::tooManyAttempts($chave, 5)) {
            $this->gerarRelatorioLog($order, 'seguranca_bloqueada_rate_limit');

            return false;
        }

        RateLimiter::hit($chave, 300);

        $pedidosRecentes = Order::where('user_id', $order->user_id)
            // o pedido ainda nao existe quando a checagem roda no checkout, e
            // "id != null" nunca e verdadeiro no SQL -- sem esta guarda a
            // consulta voltava zero sempre e a regra nao pegava nada
            ->when($order->exists, fn ($query) => $query->where('id', '!=', $order->id))
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($pedidosRecentes >= 5) {
            $this->gerarRelatorioLog($order, 'seguranca_bloqueada_muitos_pedidos');

            return false;
        }

        return true;
    }

    /**
     * Passo 3 do historico: confere a cobranca com o banco e grava o JSON.
     *
     * Devolve true so quando o credito caiu de fato. Cobranca emitida mas
     * ainda sem credito (pix e boleto) fica com o status intocado -- quem
     * chama manda para a fila de analise, onde alguem confirma o dinheiro
     * antes de a loja separar o pedido.
     *
     * SIMULACAO: a resposta vem do GatewayDePagamentoSimulado enquanto nao ha
     * API do banco parceiro.
     */
    public function verificarComBanco(Order $order): bool
    {
        $conferencia = GatewayDePagamentoSimulado::conferir($order);

        $atualizacao = ['verificado_banco' => $conferencia['liquidado']];

        if ($conferencia['status'] !== 'pendente') {
            $atualizacao['status_pagamento'] = $conferencia['status'];
        }

        $order->update($atualizacao);

        RegistroDeCompra::registrarConferencia($order->refresh(), $conferencia);

        $this->gerarRelatorioLog($order, 'conferencia_bancaria_'.$conferencia['status']);

        return $conferencia['liquidado'];
    }

    public function gerarDocumentoComprovante(Order $order, string $caminhoArquivo): Order
    {
        $order->update(['comprovante_pagamento_path' => $caminhoArquivo]);

        return $order;
    }

    /**
     * Comprovante legivel da compra, ao lado dos JSONs do mesmo pedido.
     *
     * A gravacao nao pode derrubar a compra: quando o disco recusa a escrita,
     * o pedido ja existe, o estoque ja baixou e o carrinho ja esvaziou --
     * estourar aqui deixava tudo isso feito e ainda devolvia 500 ao cliente.
     * O caminho so vai para o banco se o arquivo realmente foi escrito.
     */
    public function gerarDocumentoCompra(Order $order, string $ip, ?string $localizacao, string $codigoPagamento): Order
    {
        $order->update([
            'ip_compra' => $ip,
            'localizacao' => $localizacao,
            'codigo_pagamento' => $codigoPagamento,
        ]);

        $cliente = $order->user;

        $conteudo = implode("\n", [
            'Comprovante de Compra',
            '======================',
            'Pedido: #'.$order->id,
            'Data: '.now()->format('d/m/Y H:i:s'),
            'IP: '.$ip,
            'Localização: '.($localizacao ?? '—'),
            '',
            'Cliente: '.($cliente->name ?? '—'),
            'E-mail: '.($cliente->email ?? '—'),
            '',
            'Forma de pagamento: '.$order->forma_pagamento,
            'Código de pagamento: '.$codigoPagamento,
            'Frete: R$ '.number_format((float) ($order->valor_frete ?? 0), 2, ',', '.'),
            'Total: R$ '.number_format((float) $order->total + (float) ($order->valor_frete ?? 0), 2, ',', '.'),
        ]);

        $caminho = RegistroDeCompra::pasta($order).'/comprovante-compra.txt';

        try {
            if (Storage::disk('local')->put($caminho, $conteudo) === false) {
                throw new \RuntimeException('Storage::put devolveu false.');
            }

            $order->update(['comprovante_pagamento_path' => $caminho]);

            $this->gerarRelatorioLog($order, 'documento_compra_gerado');
        } catch (Throwable $e) {
            Log::warning('pagamento.documento_compra_falhou', [
                'order_id' => $order->id,
                'caminho' => $caminho,
                'erro' => $e->getMessage(),
            ]);
        }

        return $order;
    }

    public function gerarRelatorioLog(Order $order, string $evento): void
    {
        Log::info('pagamento.'.$evento, [
            'order_id' => $order->id,
            'status_pagamento' => $order->status_pagamento,
            'forma_pagamento' => $order->forma_pagamento,
        ]);
    }

    public function desistir(Order $order): Order
    {
        $order->update([
            'status' => 'cancelado',
            'status_pagamento' => 'recusado',
        ]);

        $this->gerarRelatorioLog($order, 'desistencia');

        return $order;
    }

    public function enviarDocParaAnalise(Order $order): Order
    {
        $order->update(['status_pagamento' => 'aguardando_analise']);

        $this->gerarRelatorioLog($order, 'enviado_para_analise');

        return $order;
    }
}
