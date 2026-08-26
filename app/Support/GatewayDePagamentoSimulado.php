<?php

namespace App\Support;

use App\Models\Order;

/**
 * ============================================================================
 * SIMULACAO -- APAGAR ESTE ARQUIVO INTEIRO NA INTEGRACAO COM O GATEWAY REAL
 * ============================================================================
 *
 * A loja ainda nao tem contrato de adquirente nem API de banco. Sem alguma
 * coisa no lugar do gateway, a compra terminava sem codigo de pagamento e sem
 * conferencia, e o pedido ficava parado na primeira etapa do acompanhamento
 * para sempre. Esta classe fecha o fluxo de ponta a ponta com dados de mentira,
 * no MESMO formato que um gateway de verdade devolve.
 *
 * Como descartar quando o gateway entrar:
 *
 *   1. Escreva um App\Support\GatewayDePagamento com os mesmos tres metodos
 *      (cobrar, conferir, gerarCodigo) chamando a API real.
 *   2. Troque as duas referencias a esta classe em App\Pages\PaginaPagamento
 *      (cobrar() e conferir()) pela nova.
 *   3. Apague este arquivo. Nada mais aponta para ele -- de proposito.
 *
 * O contrato que o resto do sistema espera, e que o gateway real tem de honrar:
 *
 *   cobrar(): array{
 *       codigo: string,           identificador da cobranca no gateway
 *       status: string,           'aprovado' | 'pendente' | 'recusado'
 *       forma: string,            pix | cartao | boleto
 *       valor: float,
 *       instrucao: ?string,       copia-e-cola do pix / linha digitavel
 *       mensagem: string,
 *       simulado: bool            <- some junto com este arquivo
 *   }
 *
 *   conferir(): array{
 *       codigo: string,
 *       liquidado: bool,          o dinheiro caiu na conta
 *       status: string,
 *       conferido_em: string,     ISO-8601
 *       mensagem: string,
 *       simulado: bool
 *   }
 */
class GatewayDePagamentoSimulado
{
    /**
     * Cria a cobranca e devolve a resposta que o gateway daria.
     *
     * Cartao aprova na hora; pix e boleto nascem pendentes e so liquidam na
     * conferencia -- e assim que os tres funcionam de verdade, e o
     * acompanhamento do pedido ja depende dessa diferenca.
     *
     * @return array<string, mixed>
     */
    public static function cobrar(Order $order, string $forma, float $valor): array
    {
        $codigo = self::gerarCodigo($order, $forma);

        $status = $forma === 'cartao' ? 'aprovado' : 'pendente';

        return [
            'codigo' => $codigo,
            'status' => $status,
            'forma' => $forma,
            'valor' => round($valor, 2),
            'instrucao' => self::instrucao($forma, $codigo),
            'mensagem' => match ($forma) {
                'cartao' => 'Pagamento autorizado pela operadora.',
                'pix' => 'Pix gerado. A confirmação chega assim que o pagamento for identificado.',
                default => 'Boleto emitido. A compensação leva até 3 dias úteis.',
            },
            'simulado' => true,
        ];
    }

    /**
     * Confere com o "banco" se a cobranca foi liquidada.
     *
     * O banco de verdade responde por codigo de cobranca; aqui a unica coisa
     * que da para checar e se o codigo existe. Cartao ja chega aprovado da
     * cobranca, entao so ele liquida -- pix e boleto seguem pendentes ate que
     * alguem confirme, exatamente como aconteceria na espera real.
     *
     * @return array<string, mixed>
     */
    public static function conferir(Order $order): array
    {
        $temCobranca = filled($order->codigo_pagamento);
        $liquidado = $temCobranca && $order->forma_pagamento === 'cartao';

        return [
            'codigo' => $order->codigo_pagamento,
            'liquidado' => $liquidado,
            'status' => match (true) {
                ! $temCobranca => 'recusado',
                $liquidado => 'aprovado',
                default => 'pendente',
            },
            'conferido_em' => now()->toIso8601String(),
            'mensagem' => match (true) {
                ! $temCobranca => 'Cobrança não localizada no gateway.',
                $liquidado => 'Crédito confirmado na conta da loja.',
                default => 'Cobrança emitida, aguardando o crédito.',
            },
            'simulado' => true,
        ];
    }

    /**
     * Identificador da cobranca.
     *
     * Forma + carimbo de tempo + id do pedido: legivel na tela de
     * acompanhamento e unico sem precisar consultar o banco.
     */
    public static function gerarCodigo(Order $order, string $forma): string
    {
        return strtoupper($forma).'-'.now()->format('YmdHis').'-'.$order->id;
    }

    /** Copia-e-cola do pix ou linha digitavel do boleto, ambos fabricados. */
    private static function instrucao(string $forma, string $codigo): ?string
    {
        return match ($forma) {
            'pix' => '00020126SIMULADO'.str_replace('-', '', $codigo).'5204000053039865802BR',
            'boleto' => implode(' ', str_split(str_pad(preg_replace('/\D/', '', $codigo) ?? '', 47, '0'), 12)),
            default => null,
        };
    }
}
