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
 * O que e mentira e o que e real, para nao confundir na hora de trocar:
 *
 *   - Mentira: a chave pix, o banco emissor, o nosso numero do boleto, a
 *     autorizacao do cartao e, claro, o dinheiro. Tudo marcado com
 *     'simulado' => true.
 *   - Real: o formato. O payload pix segue o EMV/BR Code com CRC16 correto, o
 *     boleto tem os 44 digitos com DV modulo 11 e a linha digitavel de 47 com
 *     os DVs modulo 10, e o cartao passa por Luhn. Sao esses formatos que a
 *     tela de pagamento desenha em QR e codigo de barras -- por isso eles
 *     precisam estar certos mesmo na simulacao.
 *
 * Como descartar quando o gateway entrar:
 *
 *   1. Escreva um App\Support\GatewayDePagamento com os mesmos metodos
 *      (cobrar, autorizarCartao, confirmarRecebimento, conferir, gerarCodigo)
 *      chamando a API real.
 *   2. Troque as referencias a esta classe em App\Pages\PaginaPagamento.
 *   3. Apague este arquivo. Nada mais aponta para ele -- de proposito.
 *
 * O contrato que o resto do sistema espera:
 *
 *   cobrar(): array{
 *       codigo: string,           identificador da cobranca no gateway
 *       status: string,           'aprovado' | 'pendente' | 'recusado'
 *       forma: string,            pix | cartao | boleto
 *       valor: float,
 *       instrucao: ?string,       copia-e-cola do pix / linha digitavel
 *       codigo_barras: ?string,   44 digitos do boleto, so no boleto
 *       vencimento: ?string,      data de vencimento do boleto
 *       mensagem: string,
 *       simulado: bool            <- some junto com este arquivo
 *   }
 *
 *   autorizarCartao(): array{aprovado: bool, autorizacao: ?string,
 *       bandeira: string, final: string, parcelas: int, mensagem: string,
 *       simulado: bool}
 *
 *   confirmarRecebimento(): array{recebido: bool, recebido_em: string,
 *       mensagem: string, simulado: bool}
 *
 *   conferir(): array{codigo: string, liquidado: bool, status: string,
 *       conferido_em: string, mensagem: string, simulado: bool}
 */
class GatewayDePagamentoSimulado
{
    /** Chave pix da loja. Inventada -- nenhum banco a reconhece. */
    private const CHAVE_PIX_SIMULADA = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

    /** Banco emissor do boleto simulado (341 = Itau, so para o formato). */
    private const BANCO_SIMULADO = '341';

    /** Dias ate o vencimento do boleto emitido. */
    private const BOLETO_DIAS_VENCIMENTO = 3;

    /**
     * Cartao de teste que sempre recusa.
     *
     * Um gateway de verdade oferece numeros assim para exercitar o caminho da
     * recusa. Sem um, a tela de erro do cartao nunca seria vista.
     */
    public const CARTAO_RECUSADO = '4000000000000002';

    /**
     * Emite a cobranca e devolve o instrumento de pagamento.
     *
     * Os tres nascem pendentes: ninguem pagou ainda. O cartao vira aprovado em
     * autorizarCartao(), quando o cliente preenche os dados na tela de
     * pagamento; pix e boleto, em confirmarRecebimento().
     *
     * @return array<string, mixed>
     */
    public static function cobrar(Order $order, string $forma, float $valor): array
    {
        $codigo = self::gerarCodigo($order, $forma);
        $valor = round($valor, 2);

        $cobranca = [
            'codigo' => $codigo,
            'status' => 'pendente',
            'forma' => $forma,
            'valor' => $valor,
            'instrucao' => null,
            'codigo_barras' => null,
            'vencimento' => null,
            'simulado' => true,
        ];

        // array_merge, nao o operador "+": com "+" as chaves da ESQUERDA
        // vencem, e os null do esqueleto acima engoliriam o payload do pix e
        // os digitos do boleto -- a tela abria sem QR e sem codigo de barras
        return match ($forma) {
            'pix' => array_merge($cobranca, [
                'instrucao' => self::payloadPix($order, $valor),
                'mensagem' => 'Pix gerado. Aponte a câmera para o QR Code ou copie o código.',
            ]),
            'boleto' => self::emitirBoleto($cobranca, $order, $valor),
            default => array_merge($cobranca, [
                'mensagem' => 'Informe os dados do cartão para autorizar a cobrança.',
            ]),
        };
    }

    /**
     * Autoriza (ou recusa) uma cobranca no cartao.
     *
     * A regra da simulacao: recusa o cartao de teste CARTAO_RECUSADO e
     * qualquer numero que falhe no Luhn; aprova o resto. Luhn e a mesma
     * validacao que a operadora de verdade faz antes de sair da maquininha,
     * entao vale a pena manter mesmo na simulacao.
     *
     * @param  array<string, mixed>  $dados  numero, nome, validade, cvv, parcelas
     * @return array<string, mixed>
     */
    public static function autorizarCartao(Order $order, array $dados): array
    {
        $numero = preg_replace('/\D/', '', (string) ($dados['numero'] ?? '')) ?? '';
        $parcelas = max(1, (int) ($dados['parcelas'] ?? 1));

        $recusado = $numero === self::CARTAO_RECUSADO || ! self::luhn($numero);

        return [
            'aprovado' => ! $recusado,
            'autorizacao' => $recusado ? null : strtoupper(substr(md5($order->id.$numero.now()), 0, 10)),
            'bandeira' => self::bandeira($numero),
            'final' => substr($numero, -4),
            'parcelas' => $parcelas,
            'mensagem' => $recusado
                ? 'Cobrança não autorizada pela operadora. Confira os dados ou use outro cartão.'
                : 'Pagamento autorizado em '.$parcelas.'x pela operadora.',
            'simulado' => true,
        ];
    }

    /**
     * Registra que o cliente diz ter pago o pix ou o boleto.
     *
     * No mundo real quem avisa e o banco, por webhook; aqui quem avisa e o
     * botao na tela. Por isso o pedido vai para a fila de analise em vez de
     * ser aprovado direto -- alguem ainda precisa confirmar o credito.
     *
     * @return array<string, mixed>
     */
    public static function confirmarRecebimento(Order $order): array
    {
        return [
            'recebido' => filled($order->codigo_pagamento),
            'recebido_em' => now()->toIso8601String(),
            'mensagem' => filled($order->codigo_pagamento)
                ? 'Pagamento informado. A confirmação do banco costuma levar alguns minutos.'
                : 'Cobrança não localizada no gateway.',
            'simulado' => true,
        ];
    }

    /**
     * Confere com o "banco" se a cobranca foi liquidada.
     *
     * Liquidado e o que ja chegou na conta: so o cartao autorizado. Pix e
     * boleto informados pelo cliente ficam pendentes de confirmacao.
     *
     * @return array<string, mixed>
     */
    public static function conferir(Order $order): array
    {
        $temCobranca = filled($order->codigo_pagamento);
        $liquidado = $temCobranca && $order->status_pagamento === 'aprovado';

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

    /**
     * Payload do QR Code pix, no formato EMV/BR Code.
     *
     * Estrutura de campos "ID + tamanho + valor" que o app do banco espera. A
     * chave e a de mentira, mas o CRC16 no fim e calculado de verdade: um app
     * de banco recusa o payload antes de olhar a chave se o CRC nao fechar, e
     * ai nao daria para ver a tela funcionando.
     */
    public static function payloadPix(Order $order, float $valor): string
    {
        $campo = fn (string $id, string $valor) => $id.str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT).$valor;

        // nome e cidade do recebedor entram sem acento e em caixa alta: o
        // BR Code so aceita ASCII nesses campos
        $loja = 'HR MODA ONLINE';
        $cidade = 'SAO PAULO';

        $conta = $campo('00', 'BR.GOV.BCB.PIX').$campo('01', self::CHAVE_PIX_SIMULADA);

        $payload = $campo('00', '01')                        // versao do formato
            .$campo('26', $conta)                            // conta do recebedor
            .$campo('52', '0000')                            // categoria do comerciante
            .$campo('53', '986')                             // moeda: real
            .$campo('54', number_format($valor, 2, '.', '')) // valor
            .$campo('58', 'BR')                              // pais
            .$campo('59', $loja)
            .$campo('60', $cidade)
            .$campo('62', $campo('05', 'PEDIDO'.$order->id)) // identificador
            .'6304';                                         // id e tamanho do CRC

        return $payload.self::crc16($payload);
    }

    /**
     * Boleto: 44 digitos do codigo de barras + linha digitavel de 47.
     *
     * @param  array<string, mixed>  $cobranca
     * @return array<string, mixed>
     */
    private static function emitirBoleto(array $cobranca, Order $order, float $valor): array
    {
        $vencimento = now()->addDays(self::BOLETO_DIAS_VENCIMENTO);

        $codigoBarras = self::codigoDeBarrasBoleto($order, $valor, $vencimento->format('Y-m-d'));

        return array_merge($cobranca, [
            'instrucao' => self::linhaDigitavel($codigoBarras),
            'codigo_barras' => $codigoBarras,
            'vencimento' => $vencimento->format('d/m/Y'),
            'mensagem' => 'Boleto emitido. A compensação leva até 3 dias úteis após o pagamento.',
        ]);
    }

    /**
     * Os 44 digitos do codigo de barras do boleto.
     *
     * Layout do padrao FEBRABAN: banco(3) moeda(1) DV(1) fator de
     * vencimento(4) valor(10) campo livre(25).
     */
    public static function codigoDeBarrasBoleto(Order $order, float $valor, string $vencimento): string
    {
        $fator = self::fatorVencimento($vencimento);
        $centavos = str_pad((string) (int) round($valor * 100), 10, '0', STR_PAD_LEFT);

        // campo livre: uso do banco emissor. Aqui carrega o id do pedido, o que
        // torna o boleto rastreavel de volta sem consultar mais nada
        $campoLivre = str_pad((string) $order->id, 25, '0', STR_PAD_LEFT);

        $semDv = self::BANCO_SIMULADO.'9'.$fator.$centavos.$campoLivre;

        return substr($semDv, 0, 4).self::dvGeral($semDv).substr($semDv, 4);
    }

    /**
     * Linha digitavel de 47 digitos, derivada dos 44 do codigo de barras.
     *
     * Nao e uma reordenacao cosmetica: os tres primeiros campos ganham um DV
     * modulo 10 cada, que e o que permite ao caixa detectar erro de digitacao.
     */
    public static function linhaDigitavel(string $codigoBarras): string
    {
        $banco = substr($codigoBarras, 0, 4);   // banco + moeda
        $dvGeral = substr($codigoBarras, 4, 1);
        $fatorValor = substr($codigoBarras, 5, 14);
        $campoLivre = substr($codigoBarras, 19, 25);

        $campo1 = $banco.substr($campoLivre, 0, 5);
        $campo2 = substr($campoLivre, 5, 10);
        $campo3 = substr($campoLivre, 15, 10);

        return $campo1.self::dvModulo10($campo1)
            .$campo2.self::dvModulo10($campo2)
            .$campo3.self::dvModulo10($campo3)
            .$dvGeral
            .$fatorValor;
    }

    /** Linha digitavel na mascara que o cliente le: 5.5 5.6 5.6 1 14. */
    public static function formatarLinhaDigitavel(string $linha): string
    {
        return substr($linha, 0, 5).'.'.substr($linha, 5, 5)
            .'  '.substr($linha, 10, 5).'.'.substr($linha, 15, 6)
            .'  '.substr($linha, 21, 5).'.'.substr($linha, 26, 6)
            .'  '.substr($linha, 32, 1)
            .'  '.substr($linha, 33);
    }

    /**
     * Fator de vencimento: dias desde 07/10/1997.
     *
     * Passando de 9999 a contagem reinicia em 1000, como a FEBRABAN definiu ao
     * se aproximar do estouro de quatro digitos -- ja aconteceu em 2025, entao
     * uma data de hoje cai justamente na faixa reiniciada.
     */
    private static function fatorVencimento(string $vencimento): string
    {
        $base = new \DateTimeImmutable('1997-10-07');
        $dias = (int) $base->diff(new \DateTimeImmutable($vencimento))->days;

        if ($dias > 9999) {
            $dias = ($dias - 1000) % 9000 + 1000;
        }

        return str_pad((string) $dias, 4, '0', STR_PAD_LEFT);
    }

    /**
     * DV geral do codigo de barras: modulo 11, pesos de 2 a 9 da direita.
     *
     * Resultado 0, 10 ou 11 vira 1, por definicao do padrao.
     */
    private static function dvGeral(string $digitos43): string
    {
        $soma = 0;
        $peso = 2;

        for ($i = strlen($digitos43) - 1; $i >= 0; $i--) {
            $soma += (int) $digitos43[$i] * $peso;
            $peso = $peso === 9 ? 2 : $peso + 1;
        }

        $dv = 11 - $soma % 11;

        return (string) (($dv === 0 || $dv > 9) ? 1 : $dv);
    }

    /** DV de campo da linha digitavel: modulo 10, pesos 2 e 1 alternados. */
    private static function dvModulo10(string $campo): string
    {
        $soma = 0;
        $peso = 2;

        for ($i = strlen($campo) - 1; $i >= 0; $i--) {
            $produto = (int) $campo[$i] * $peso;
            // produto de dois digitos entra como a soma dos seus digitos
            $soma += $produto > 9 ? $produto - 9 : $produto;
            $peso = $peso === 2 ? 1 : 2;
        }

        return (string) ((10 - $soma % 10) % 10);
    }

    /** CRC16-CCITT (polinomio 0x1021), como o BR Code exige. */
    private static function crc16(string $payload): string
    {
        $crc = 0xFFFF;

        for ($i = 0; $i < strlen($payload); $i++) {
            $crc ^= ord($payload[$i]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /** Validacao de Luhn, o digito verificador de todo cartao. */
    public static function luhn(string $numero): bool
    {
        if (strlen($numero) < 13 || strlen($numero) > 19) {
            return false;
        }

        $soma = 0;
        $dobrar = false;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];

            if ($dobrar) {
                $digito *= 2;

                if ($digito > 9) {
                    $digito -= 9;
                }
            }

            $soma += $digito;
            $dobrar = ! $dobrar;
        }

        return $soma % 10 === 0;
    }

    /** Bandeira pelo prefixo do numero -- so para exibir na tela. */
    public static function bandeira(string $numero): string
    {
        // Elo antes de Visa e Mastercard de proposito: as faixas dela comecam
        // com 4 e 5, entao a regra generica engoliria todas
        return match (true) {
            (bool) preg_match('/^(4011|4312|4389|4514|4573|6362|6363|5067|5090)/', $numero) => 'Elo',
            (bool) preg_match('/^3[47]/', $numero) => 'American Express',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $numero) => 'Mastercard',
            str_starts_with($numero, '4') => 'Visa',
            (bool) preg_match('/^(606282|3841)/', $numero) => 'Hipercard',
            default => 'Cartão',
        };
    }
}
