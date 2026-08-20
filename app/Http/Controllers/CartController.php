<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Pages\PaginaCompra;
use App\Pages\PaginaInicial;
use App\Pages\PaginaPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $itens = (new PaginaInicial)->carrinho($request->user()->id);

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

        try {
            (new PaginaCompra)->adicionarMais(
                $request->user()->id,
                $data['product_id'],
                $data['quantidade'] ?? 1,
                $data['tamanho'] ?? null,
                $data['cor'] ?? null,
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

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
        (new PaginaCompra)->limpar($request->user()->id);

        return back()->with('status', 'Carrinho esvaziado.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'forma_pagamento' => ['required', 'in:pix,cartao,boleto'],
            'tipo_entrega' => ['required', 'in:retirada,entrega'],
            'distancia_km' => ['nullable', 'numeric', 'min:0', 'max:200'],
        ]);

        $pagamento = new PaginaPagamento;

        if (! $pagamento->validarSeguranca(
            new Order(['user_id' => $user->id]),
            $request->ip()
        )) {
            return back()->withErrors(['seguranca' => 'Não foi possível validar essa compra por segurança. Tente novamente em alguns minutos.']);
        }

        $formaPagamento = (new PaginaCompra)->selecionaFormaPagamento($data['forma_pagamento']);

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

        $distanciaKm = (float) ($data['distancia_km'] ?? 3);
        $valorFrete = $data['tipo_entrega'] === 'entrega' ? (new PaginaCompra)->calcularFrete($distanciaKm) : 0.0;

        $order = DB::transaction(function () use ($user, $itens, $data, $formaPagamento, $distanciaKm, $valorFrete) {
            $subtotal = $itens->sum(fn ($item) => $item->product->preco * $item->quantidade);

            $order = Order::create([
                'user_id' => $user->id,
                'total' => $subtotal,
                'distancia_km' => $data['tipo_entrega'] === 'entrega' ? $distanciaKm : null,
                'valor_frete' => $valorFrete,
                'status' => 'concluido',
                'forma_pagamento' => $formaPagamento,
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

        match ($formaPagamento) {
            'pix' => $pagamento->pix($order),
            'cartao' => $pagamento->cartao($order),
            'boleto' => $pagamento->boleto($order),
        };

        $codigoPagamento = strtoupper($formaPagamento).'-'.now()->format('YmdHis').'-'.$order->id;
        $pagamento->gerarDocumentoCompra($order, $request->ip(), $user->endereco, $codigoPagamento);

        if ($pagamento->verificarComBanco($order->fresh())) {
            $pagamento->enviarDocParaAnalise($order->fresh());
        }

        return redirect()->route('orders.index')->with('status', 'Pedido #'.$order->id.' realizado com sucesso!');
    }
}
