<?php

namespace App\Pages;

use App\Classes\Loja;
use App\Interfaces\Compra;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\RegistroDeCompra;
use App\Support\RotaDeEntrega;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PaginaCompra implements Compra
{
    /** Piso do frete: toda entrega custa ao menos isto, em qualquer distancia. */
    public const FRETE_MINIMO = 12.0;

    /** Km cobertos pelo piso. So o que passa daqui e cobrado a parte. */
    public const FRETE_KM_INCLUSOS = 6.0;

    /** Preco de cada km acima da faixa inclusa. */
    public const FRETE_POR_KM_EXCEDENTE = 5.0;

    /**
     * Teto do frete, cobrado em qualquer distancia acima do que ele cobre.
     *
     * Sem teto a regra por km levava rota interestadual a milhares de reais --
     * Sao Paulo/Recife dava R$ 13.837 de frete numa peca de R$ 190. O teto
     * mantem a conta por km onde ela faz sentido e corta o absurdo.
     */
    public const FRETE_MAXIMO = 60.0;

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

    /**
     * Frete de uma entrega, a partir da rota calculada pela RotaDeEntrega.
     *
     * Regra: R$ 12,00 e o piso, cobrado em qualquer distancia. Passando de
     * 6 km, cada km alem dos 6 custa R$ 5,00. Km quebrado conta inteiro --
     * 6,2 km ja e um km excedente -- porque a rota e uma estimativa e
     * arredondar para baixo entregaria de graca o trecho que sobra.
     *
     * O teto de R$ 60,00 corta a conta a partir de 16 km.
     *
     * 6 km -> R$ 12,00    8 km -> R$ 22,00    16 km -> R$ 60,00    500 km -> R$ 60,00
     */
    public function calcularFrete(float $distanciaKm): float
    {
        $kmExcedentes = max(0, (int) ceil($distanciaKm - self::FRETE_KM_INCLUSOS));

        $frete = self::FRETE_MINIMO + ($kmExcedentes * self::FRETE_POR_KM_EXCEDENTE);

        return min($frete, self::FRETE_MAXIMO);
    }

    /**
     * Rota automatica loja -> cliente e o frete que ela custa.
     *
     * O cliente nao informa distancia: ela sai do ponto de despacho das lojas
     * do carrinho e do endereco cadastrado no perfil. Antes esse numero vinha
     * de um campo do formulario, ou seja, o comprador escolhia o proprio frete.
     *
     * @param  iterable<int, Loja>  $lojas
     * @return array{distancia_km: float, valor_frete: float}
     */
    public function calcularFreteAutomatico(iterable $lojas, User $cliente): array
    {
        $distanciaKm = RotaDeEntrega::kmDoPedido($lojas, $cliente);

        return [
            'distancia_km' => $distanciaKm,
            'valor_frete' => $this->calcularFrete($distanciaKm),
        ];
    }

    /**
     * Passo 1 do historico: registra em JSON o que o cliente selecionou.
     */
    public function registrarSelecao(Order $order): ?string
    {
        return RegistroDeCompra::registrarSelecao($order);
    }

    public function calcularComissao(float $valor, ?float $taxaPlano = null): float
    {
        $taxa = $taxaPlano ?? 10.0;

        return round($valor * ($taxa / 100), 2);
    }
}
