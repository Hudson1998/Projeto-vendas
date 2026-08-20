<?php

namespace App\Pages;

use App\Interfaces\Pagamento;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

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
            ->where('id', '!=', $order->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($pedidosRecentes >= 5) {
            $this->gerarRelatorioLog($order, 'seguranca_bloqueada_muitos_pedidos');

            return false;
        }

        return true;
    }

    public function verificarComBanco(Order $order): bool
    {
        // API do banco parceiro ainda não foi desenvolvida: verificação simulada.
        $aprovado = filled($order->codigo_pagamento);

        $order->update(['verificado_banco' => $aprovado]);

        $this->gerarRelatorioLog($order, $aprovado ? 'verificacao_banco_simulada_ok' : 'verificacao_banco_simulada_falhou');

        return $aprovado;
    }

    public function gerarDocumentoComprovante(Order $order, string $caminhoArquivo): Order
    {
        $order->update(['comprovante_pagamento_path' => $caminhoArquivo]);

        return $order;
    }

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
            'Total: R$ '.number_format((float) $order->total + (float) ($order->valor_frete ?? 0), 2, ',', '.'),
        ]);

        $caminho = "pedidos/{$order->id}/comprovante-compra.txt";
        Storage::disk('local')->put($caminho, $conteudo);

        $order->update(['comprovante_pagamento_path' => $caminho]);

        $this->gerarRelatorioLog($order, 'documento_compra_gerado');

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
