<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $itens = CartItem::with('product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        $total = $itens->sum(fn ($item) => $item->product->preco * $item->quantidade);

        return view('cart.index', [
            'itens' => $itens,
            'total' => $total,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantidade' => ['nullable', 'integer', 'min:1', 'max:20'],
            'tamanho' => ['nullable', 'string', 'max:60'],
            'cor' => ['nullable', 'string', 'max:60'],
        ]);

        $product = Product::with('variants')->findOrFail($data['product_id']);
        $quantidadeDesejada = $data['quantidade'] ?? 1;
        $tamanho = $data['tamanho'] ?? null;
        $cor = $data['cor'] ?? null;

        $variante = $product->variantePara($tamanho, $cor);

        if (!$variante) {
            return response()->json(['message' => 'Selecione um tamanho/cor válido.'], 422);
        }

        if ($variante->estoque < 1) {
            return response()->json(['message' => 'Peça esgotada.'], 422);
        }

        $item = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'tamanho' => $tamanho,
            'cor' => $cor,
        ]);

        $novaQuantidade = ($item->quantidade ?? 0) + $quantidadeDesejada;
        if ($novaQuantidade > $variante->estoque) {
            return response()->json(['message' => 'Quantidade indisponível em estoque.'], 422);
        }

        $item->quantidade = $novaQuantidade;
        $item->save();

        $quantidadeTotal = CartItem::where('user_id', $request->user()->id)->sum('quantidade');

        return response()->json([
            'message' => 'Peça adicionada ao carrinho.',
            'quantidadeCarrinho' => $quantidadeTotal,
        ]);
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'quantidade' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $cartItem->update(['quantidade' => $data['quantidade']]);

        return back();
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('status', 'Item removido do carrinho.');
    }

    public function clear(Request $request): RedirectResponse
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return back()->with('status', 'Carrinho esvaziado.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'forma_pagamento' => ['required', 'in:pix,cartao,boleto'],
            'tipo_entrega' => ['required', 'in:retirada,entrega'],
        ]);

        $itens = CartItem::with('product.variants')->where('user_id', $user->id)->get();

        if ($itens->isEmpty()) {
            return back()->withErrors(['carrinho' => 'Seu carrinho está vazio.']);
        }

        if ($data['tipo_entrega'] === 'entrega' && ! $user->endereco) {
            return back()->withErrors(['tipo_entrega' => 'Cadastre um endereço no seu perfil antes de escolher entrega.']);
        }

        foreach ($itens as $item) {
            $variante = $item->product->variantePara($item->tamanho, $item->cor);

            if (! $variante || $item->quantidade > $variante->estoque) {
                return back()->withErrors(['carrinho' => 'A peça "'.$item->product->nome.'" não tem mais estoque suficiente.']);
            }
        }

        $order = DB::transaction(function () use ($user, $itens, $data) {
            $order = Order::create([
                'user_id' => $user->id,
                'total' => $itens->sum(fn ($item) => $item->product->preco * $item->quantidade),
                'status' => 'concluido',
                'forma_pagamento' => $data['forma_pagamento'],
                'tipo_entrega' => $data['tipo_entrega'],
                'endereco_entrega' => $data['tipo_entrega'] === 'entrega' ? $user->endereco : null,
            ]);

            foreach ($itens as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantidade' => $item->quantidade,
                    'preco_unitario' => $item->product->preco,
                    'tamanho' => $item->tamanho,
                    'cor' => $item->cor,
                ]);

                $item->product->variantePara($item->tamanho, $item->cor)->decrement('estoque', $item->quantidade);
            }

            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });

        return redirect()->route('orders.index')->with('status', 'Pedido #'.$order->id.' realizado com sucesso!');
    }
}
