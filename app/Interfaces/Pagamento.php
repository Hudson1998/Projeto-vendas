<?php

namespace App\Interfaces;

use App\Models\Order;

interface Pagamento
{
    public function pix(Order $order): Order;

    public function cartao(Order $order): Order;

    public function boleto(Order $order): Order;

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
