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
        $user = $request->user();

        $itens = (new PaginaInicial)->carrinho($user->id);

        $total = $itens->sum(fn ($item) => $item->product->preco * $item->quantidade);

        // a mesma rota que o checkout vai cobrar, calculada aqui so para o
        // cliente ver o frete antes de confirmar -- o valor gravado no pedido
        // e sempre recalculado no servidor, nunca o que a tela mostrou
        $rota = (new PaginaCompra)->calcularFreteAutomatico(
            $itens->pluck('product.loja')->filter(),
            $user,
        );

        return view('cart.index', [
            'itens' => $itens,
            'total' => $total,
            'rota' => $rota,
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

        // a distancia nao vem mais do formulario: o cliente escolhia o proprio
        // frete digitando o km. Ela sai da rota loja -> endereco do perfil.
        $data = $request->validate([
            'forma_pagamento' => ['required', 'in:pix,cartao,boleto'],
            'tipo_entrega' => ['required', 'in:retirada,entrega'],
        ]);

        $pagamento = new PaginaPagamento;

        if (! $pagamento->validarSeguranca(
            new Order(['user_id' => $user->id]),
            $request->ip()
        )) {
            return back()->withErrors(['seguranca' => 'Não foi possível validar essa compra por segurança. Tente novamente em alguns minutos.']);
        }

        $formaPagamento = (new PaginaCompra)->selecionaFormaPagamento($data['forma_pagamento']);

        $itens = CartItem::with('product.variants', 'product.loja')->where('user_id', $user->id)->get();

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

        $entrega = $data['tipo_entrega'] === 'entrega';

        // rota automatica: ponto de despacho das lojas do carrinho ate o
        // endereco cadastrado do cliente. Retirada na loja nao percorre rota.
        $rota = $entrega
            ? (new PaginaCompra)->calcularFreteAutomatico($itens->pluck('product.loja')->filter(), $user)
            : ['distancia_km' => 0.0, 'valor_frete' => 0.0];

        $order = DB::transaction(function () use ($user, $itens, $data, $entrega, $formaPagamento, $rota, $request) {
            $subtotal = $itens->sum(fn ($item) => $item->product->preco * $item->quantidade);

            $order = Order::create([
                'user_id' => $user->id,
                'total' => $subtotal,
                'distancia_km' => $entrega ? $rota['distancia_km'] : null,
                'valor_frete' => $rota['valor_frete'],
                'status' => 'concluido',
                'forma_pagamento' => $formaPagamento,
                'tipo_entrega' => $data['tipo_entrega'],
                'endereco_entrega' => $entrega ? $user->endereco : null,
                // gravados junto com o pedido para que o JSON do pagamento,
                // logo abaixo, ja saia com a origem da compra preenchida
                'ip_compra' => $request->ip(),
                'localizacao' => $user->endereco,
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

        // Os tres passos da compra, cada um deixando o seu JSON em
        // storage/app/private/historico-clientes/{cliente}/pedidos/{pedido}/.
        // Nenhum deles pode derrubar a requisicao: daqui para baixo o pedido ja
        // esta pago, o estoque ja baixou e o carrinho ja esvaziou -- uma falha
        // de escrita vira aviso no log, nunca 500 na cara do cliente.

        // 1. selecao das pecas
        (new PaginaCompra)->registrarSelecao($order);

        // 2. cobranca (SIMULACAO -- ver App\Support\GatewayDePagamentoSimulado)
        $cobranca = $pagamento->cobrar($order, $formaPagamento);

        $pagamento->gerarDocumentoCompra($order, $request->ip(), $user->endereco, $cobranca['codigo']);

        // O passo 3, a conferencia, acontece na tela de pagamento: e la que o
        // cliente encontra o QR do pix, o codigo de barras do boleto ou o
        // formulario do cartao. Antes a compra se dava por paga aqui mesmo e
        // ninguem chegava a ver como pagar.
        return redirect()->route('orders.pagamento', $order)
            ->with('status', 'Pedido #'.$order->id.' registrado. Falta só o pagamento.');
    }
}
