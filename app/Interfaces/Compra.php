<?php

namespace App\Interfaces;

use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface Compra
{
    public function produtos(?string $categoria = null): Collection;

    public function selecionaFormaPagamento(string $forma): string;

    public function limpar(int $userId): void;

    public function adicionarMais(int $userId, int $productId, int $quantidade = 1, ?string $tamanho = null, ?string $cor = null): CartItem;

    public function calcularFrete(float $distanciaKm): float;

    public function calcularComissao(float $valor, ?float $taxaPlano = null): float;
}
