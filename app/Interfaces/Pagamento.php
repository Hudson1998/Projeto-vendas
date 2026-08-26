<?php

namespace App\Interfaces;

use App\Models\Order;

interface Pagamento
{
    public function pix(Order $order): Order;

    public function cartao(Order $order): Order;

    public function boleto(Order $order): Order;

    /**
     * Emite a cobranca no gateway e registra o passo de pagamento.
     *
     * @return array<string, mixed> resposta do gateway
     */
    public function cobrar(Order $order, string $forma): array;

    /**
     * A cobranca ja emitida, para a tela de pagamento redesenhar o instrumento.
     *
     * @return array<string, mixed>|null
     */
    public function cobrancaEmitida(Order $order): ?array;

    /**
     * @param  array<string, mixed>  $dadosCartao
     * @return array<string, mixed>
     */
    public function autorizarCartao(Order $order, array $dadosCartao): array;

    /** @return array<string, mixed> */
    public function confirmarRecebimento(Order $order): array;

    public function autenticarTransferencia(Order $order): bool;

    public function confirmarDados(Order $order): bool;

    public function validarSeguranca(Order $order, string $ip): bool;

    public function verificarComBanco(Order $order): bool;

    public function gerarDocumentoComprovante(Order $order, string $caminhoArquivo): Order;

    public function gerarDocumentoCompra(Order $order, string $ip, ?string $localizacao, string $codigoPagamento): Order;

    public function gerarRelatorioLog(Order $order, string $evento): void;

    public function desistir(Order $order): Order;

    public function enviarDocParaAnalise(Order $order): Order;
}
