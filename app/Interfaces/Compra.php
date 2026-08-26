<?php

namespace App\Interfaces;

use App\Classes\Loja;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface Compra
{
    public function produtos(?string $categoria = null): Collection;

    public function selecionaFormaPagamento(string $forma): string;

    public function limpar(int $userId): void;

    public function adicionarMais(int $userId, int $productId, int $quantidade = 1, ?string $tamanho = null, ?string $cor = null): CartItem;

    public function calcularFrete(float $distanciaKm): float;

    /**
     * @param  iterable<int, Loja>  $lojas
     * @return array{distancia_km: float, valor_frete: float}
     */
    public function calcularFreteAutomatico(iterable $lojas, User $cliente): array;

    public function registrarSelecao(Order $order): ?string;

    public function calcularComissao(float $valor, ?float $taxaPlano = null): float;
}
