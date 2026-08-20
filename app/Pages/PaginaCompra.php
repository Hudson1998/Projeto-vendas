<?php

namespace App\Pages;

use App\Interfaces\Compra;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PaginaCompra implements Compra
{
    public function produtos(?string $categoria = null): Collection
    {
        return Product::query()
            ->with('variants')
            ->when($categoria, fn ($query) => $query->where('categoria', $categoria))
            ->orderBy('nome')
            ->get();
    }

    public function selecionaFormaPagamento(string $forma): string
    {
        if (! in_array($forma, ['pix', 'cartao', 'boleto'], true)) {
            throw ValidationException::withMessages([
                'forma_pagamento' => 'Forma de pagamento inválida.',
            ]);
        }

        return $forma;
    }

    public function limpar(int $userId): void
    {
        CartItem::where('user_id', $userId)->delete();
    }

    public function adicionarMais(int $userId, int $productId, int $quantidade = 1, ?string $tamanho = null, ?string $cor = null): CartItem
    {
        $product = Product::with('variants')->findOrFail($productId);
        $variante = $product->variantePara($tamanho, $cor);

        if (! $variante) {
            throw ValidationException::withMessages([
                'produto' => 'Selecione um tamanho/cor válido.',
            ]);
        }

        $item = CartItem::firstOrNew([
            'user_id' => $userId,
            'product_id' => $product->id,
            'tamanho' => $tamanho,
            'cor' => $cor,
        ]);

        $novaQuantidade = ($item->quantidade ?? 0) + $quantidade;

        if ($novaQuantidade > $variante->estoque) {
            throw ValidationException::withMessages([
                'quantidade' => 'Quantidade indisponível em estoque.',
            ]);
        }

        $item->quantidade = $novaQuantidade;
        $item->save();

        return $item;
    }

    public function calcularFrete(float $distanciaKm): float
    {
        if ($distanciaKm <= 3) {
            return 12.0;
        }

        $faixasAdicionais = (int) ceil(($distanciaKm - 3) / 6);

        return 12.0 + ($faixasAdicionais * 10.0);
    }

    public function calcularComissao(float $valor, ?float $taxaPlano = null): float
    {
        $taxa = $taxaPlano ?? 10.0;

        return round($valor * ($taxa / 100), 2);
    }
}
