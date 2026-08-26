<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Pages\PaginaPagamento;
use App\Support\CodigoDeBarras;
use App\Support\GatewayDePagamentoSimulado;
use App\Support\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tela de pagamento do pedido, uma para cada forma.
 *
 * O checkout so emite a cobranca; quem a conclui e esta tela -- QR e
 * copia-e-cola no pix, codigo de barras e linha digitavel no boleto,
 * formulario do cartao no cartao. Antes a compra terminava sozinha no
 * "Confirmar pedido" e o cliente nunca via como pagar.
 */
class PagamentoController extends Controller
{
    public function show(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $pagamento = new PaginaPagamento;

        // pedido ja pago (ou ja na fila de analise) nao volta para a tela de
        // cobranca: sem isso um F5 depois de pagar reabriria o formulario
        if ($this->jaResolvido($order)) {
            return redirect()->route('orders.tracking');
        }

        $cobranca = $pagamento->cobrancaEmitida($order);

        if ($cobranca === null) {
            return redirect()->route('orders.tracking')
                ->withErrors(['pagamento' => 'Esse pedido não tem uma cobrança em aberto.']);
        }

        return view('orders.pagamento', [
            'pedido' => $order,
            'cobranca' => $cobranca,
            'instrumento' => $this->desenharInstrumento($cobranca),
            'parcelasDisponiveis' => $this->parcelas((float) $cobranca['valor']),
        ]);
    }

    /**
     * Conclui o pagamento: autoriza o cartao ou registra o pix/boleto pago.
     */
    public function processar(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($this->jaResolvido($order)) {
            return redirect()->route('orders.tracking');
        }

        $pagamento = new PaginaPagamento;

        if ($order->forma_pagamento === 'cartao') {
            $dados = $request->validate([
                'numero' => ['required', 'string', 'min:13', 'max:23'],
                'titular' => ['required', 'string', 'max:120'],
                'validade' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
                'cvv' => ['required', 'string', 'regex:/^\d{3,4}$/'],
                'parcelas' => ['required', 'integer', 'min:1', 'max:12'],
            ], [
                'validade.regex' => 'Informe a validade no formato MM/AA.',
                'cvv.regex' => 'O código de segurança tem 3 ou 4 dígitos.',
            ]);

            if ($this->validadeExpirada($dados['validade'])) {
                return back()->withErrors(['validade' => 'Esse cartão está vencido.'])->withInput();
            }

            $autorizacao = $pagamento->autorizarCartao($order, $dados);

            if (! $autorizacao['aprovado']) {
                // o pedido segue pendente de proposito: o cliente corrige os
                // dados e tenta de novo na mesma tela
                return back()->withErrors(['numero' => $autorizacao['mensagem']])->withInput();
            }

            $mensagem = 'Pagamento aprovado! Pedido #'.$order->id.' confirmado.';
        } else {
            $recibo = $pagamento->confirmarRecebimento($order);

            if (! $recibo['recebido']) {
                return back()->withErrors(['pagamento' => $recibo['mensagem']]);
            }

            $mensagem = $order->forma_pagamento === 'pix'
                ? 'Pix informado! Assim que o banco confirmar, o pedido segue para a loja.'
                : 'Boleto registrado! A compensação leva até 3 dias úteis.';
        }

        // passo 3 do historico: a conferencia com o banco, com o JSON
        $pagamento->verificarComBanco($order->refresh());

        return redirect()->route('orders.tracking')->with('status', $mensagem);
    }

    /** Pagamento que ja saiu da mao do cliente: nao ha o que cobrar de novo. */
    private function jaResolvido(Order $order): bool
    {
        return $order->status === 'cancelado'
            || in_array($order->status_pagamento, ['aprovado', 'aguardando_analise', 'recusado'], true);
    }

    /**
     * As imagens do instrumento: QR do pix, barras do boleto.
     *
     * Fica no controller e nao na view porque sao SVG gerados, nao markup --
     * a view so imprime o que vier.
     *
     * @param  array<string, mixed>  $cobranca
     * @return array<string, string|null>
     */
    private function desenharInstrumento(array $cobranca): array
    {
        return [
            'qrcode' => $cobranca['forma'] === 'pix' && filled($cobranca['instrucao'])
                ? QrCode::svg($cobranca['instrucao'], 5)
                : null,
            'barras' => $cobranca['forma'] === 'boleto' && filled($cobranca['codigo_barras'] ?? null)
                ? CodigoDeBarras::svg($cobranca['codigo_barras'], 2, 90)
                : null,
            'linha_digitavel' => $cobranca['forma'] === 'boleto' && filled($cobranca['instrucao'])
                ? GatewayDePagamentoSimulado::formatarLinhaDigitavel($cobranca['instrucao'])
                : null,
        ];
    }

    /**
     * Opcoes de parcelamento do cartao.
     *
     * Sem juros e com parcela minima de R$ 20,00, para nao oferecer 12x de
     * troco. Simulacao: a operadora real e quem define as faixas.
     *
     * @return array<int, array{parcelas: int, valor: float, rotulo: string}>
     */
    private function parcelas(float $total): array
    {
        $maximo = max(1, min(12, (int) floor($total / 20)));
        $opcoes = [];

        for ($n = 1; $n <= $maximo; $n++) {
            $valor = round($total / $n, 2);

            $opcoes[] = [
                'parcelas' => $n,
                'valor' => $valor,
                'rotulo' => $n.'x de R$ '.number_format($valor, 2, ',', '.').($n === 1 ? ' à vista' : ' sem juros'),
            ];
        }

        return $opcoes;
    }

    /** Validade MM/AA ja passada. */
    private function validadeExpirada(string $validade): bool
    {
        [$mes, $ano] = explode('/', $validade);

        $fimDoMes = now()->setDate(2000 + (int) $ano, (int) $mes, 1)->endOfMonth();

        return $fimDoMes->isPast();
    }
}
