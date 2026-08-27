<?php

namespace App\Http\Controllers;

use App\Classes\Loja;
use App\Models\Order;
use App\Models\Product;
use App\Pages\PaginaAnalise;
use App\Pages\PaginaLojaDashboard;
use App\Support\CarteiraDaLoja;
use App\Support\DocumentoDePedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * O que o lojista faz no painel alem de olhar numeros: carteira, catalogo,
 * esteira de pedidos e documentos.
 *
 * Fica separado do LojaDashboardController de proposito -- aquele responde
 * pelas telas de leitura (dashboard, clientes, transportadoras) e este pelas
 * acoes que mexem em dinheiro, catalogo e estado de pedido.
 */
class LojaPainelController extends Controller
{
    /** As colunas da esteira, na ordem em que o pedido anda. */
    private const ESTEIRA = [
        'aprovar' => ['rotulo' => 'A aprovar', 'ajuda' => 'Pedidos pagos esperando o aceite da loja.'],
        'separar' => ['rotulo' => 'Em separação', 'ajuda' => 'Aceitos. Separe as peças no estoque.'],
        'transporte' => ['rotulo' => 'Pronto para o transporte', 'ajuda' => 'Separados, aguardando a coleta.'],
        'caminho' => ['rotulo' => 'A caminho', 'ajuda' => 'Com o transporte. Confirme quando o cliente receber.'],
    ];

    private function lojaAtual(Request $request): Loja
    {
        return $request->user()->loja ?? abort(404);
    }

    /** O pedido tem alguma peca desta loja? */
    private function autorizarPedido(Request $request, Order $order): void
    {
        abort_unless(
            $order->items()->whereHas('product', fn ($q) => $q->where('loja_id', $this->lojaAtual($request)->id))->exists(),
            403
        );
    }

    // ===================== Carteira =====================

    public function carteira(Request $request): View
    {
        $loja = $this->lojaAtual($request);

        return view('loja.carteira', [
            'loja' => $loja,
            'carteira' => CarteiraDaLoja::resumo($loja),
            'saques' => CarteiraDaLoja::extrato($loja, 15),
        ]);
    }

    public function sacar(Request $request): RedirectResponse
    {
        $loja = $this->lojaAtual($request);

        $data = $request->validate([
            'valor' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $saque = CarteiraDaLoja::sacar($loja, (float) $data['valor']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('status', 'Saque de R$ '.number_format((float) $saque->valor, 2, ',', '.').' solicitado.');
    }

    // ===================== Catalogo =====================

    public function criarProduto(Request $request): RedirectResponse
    {
        $loja = $this->lojaAtual($request);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0.01'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tamanho' => ['nullable', 'string', 'max:60'],
            'cor' => ['nullable', 'string', 'max:60'],
            'estoque' => ['required', 'integer', 'min:0'],
        ]);

        $foto = $request->file('foto');
        $nomeArquivo = uniqid('produto_').'.'.$foto->getClientOriginalExtension();
        $foto->move(public_path('uploads'), $nomeArquivo);

        $produto = Product::create([
            // o loja_id e o ponto todo: sem ele a peca nasce orfa e nao aparece
            // no painel de ninguem -- e o que acontece no cadastro do admin
            'loja_id' => $loja->id,
            'nome' => $data['nome'],
            'categoria' => $data['categoria'],
            'preco' => $data['preco'],
            'descricao' => $data['descricao'] ?? null,
            'imagem' => 'uploads/'.$nomeArquivo,
        ]);

        // estoque vive em product_variants; sem ao menos uma variante a peca
        // aparece na vitrine e nao pode ser comprada
        $produto->variants()->create([
            'tamanho' => $data['tamanho'] ?: null,
            'cor' => $data['cor'] ?: null,
            'cor_hex' => ! empty($data['cor']) ? '#000000' : null,
            'estoque' => $data['estoque'],
        ]);

        return back()->with('status', 'Peça "'.$produto->nome.'" cadastrada.');
    }

    public function removerProduto(Request $request, Product $product): RedirectResponse
    {
        $loja = $this->lojaAtual($request);

        abort_unless($product->loja_id === $loja->id, 403);

        // peca ja vendida nao some: os pedidos antigos apontam para ela e o
        // historico do cliente ficaria com um item sem nome
        if ($product->orderItems()->exists()) {
            return back()->withErrors([
                'produto' => 'A peça "'.$product->nome.'" já foi vendida e não pode ser removida. Zere o estoque para tirá-la da vitrine.',
            ]);
        }

        $nome = $product->nome;
        $product->variants()->delete();
        $product->delete();

        return back()->with('status', 'Peça "'.$nome.'" removida.');
    }

    // ===================== Esteira de pedidos =====================

    public function esteira(Request $request): View
    {
        $loja = $this->lojaAtual($request);

        $pedidos = Order::with('user', 'items.product', 'transportadora', 'motorista')
            ->whereHas('items.product', fn ($q) => $q->where('loja_id', $loja->id))
            ->whereIn('status_pagamento', ['aprovado', 'aguardando_analise'])
            ->where('status', '!=', 'cancelado')
            ->latest()
            ->get();

        return view('loja.esteira', [
            'loja' => $loja,
            'colunas' => self::ESTEIRA,
            'pedidos' => $pedidos->groupBy(fn (Order $o) => match ($o->status_separacao) {
                'entregue' => 'entregue',
                'enviado' => 'caminho',
                'separado', 'embalado' => 'transporte',
                'aceito' => 'separar',
                default => 'aprovar',
            }),
        ]);
    }

    /** A loja aceita o pedido: sai da fila de aprovacao e entra na separacao. */
    public function aceitar(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        (new PaginaAnalise)->aceitarNaLoja($order);

        DocumentoDePedido::registrarAceite($order, $this->lojaAtual($request)->id);

        return back()->with('status', 'Pedido #'.$order->id.' aceito. Documento de aceite emitido.');
    }

    /** Pecas separadas e prontas para a coleta. */
    public function separar(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        (new PaginaAnalise)->separar($order);

        return back()->with('status', 'Pedido #'.$order->id.' separado.');
    }

    /** Mercadoria entregue a quem transporta. */
    public function despachar(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        (new PaginaAnalise)->enviar($order);

        DocumentoDePedido::registrarTransporte($order, $this->lojaAtual($request)->id);

        return back()->with('status', 'Pedido #'.$order->id.' entregue ao transporte. Documento emitido.');
    }

    /** Chegou nas maos do cliente: fim do fluxo. */
    public function entregar(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        (new PaginaAnalise)->entregar($order);

        DocumentoDePedido::registrarEntrega($order, $this->lojaAtual($request)->id);

        return back()->with('status', 'Pedido #'.$order->id.' entregue ao cliente. Documento emitido.');
    }

    // ===================== Documentos =====================

    public function documentos(Request $request): View
    {
        $loja = $this->lojaAtual($request);

        return view('loja.documentos', [
            'loja' => $loja,
            'documentos' => DocumentoDePedido::daLoja($loja->id),
            'rotulos' => DocumentoDePedido::ROTULOS,
        ]);
    }

    // ===================== Dados dos graficos (Angular) =====================

    public function graficoLucro(Request $request): JsonResponse
    {
        return response()->json((new PaginaLojaDashboard)->lucroPorPeriodo(
            $this->lojaAtual($request)->id,
            $request->query('granularidade', 'dia'),
        ));
    }

    public function graficoVisitantes(Request $request): JsonResponse
    {
        return response()->json((new PaginaLojaDashboard)->visitantesPorPeriodo(
            $this->lojaAtual($request)->id,
            $request->query('granularidade', 'dia'),
        ));
    }

    public function graficoVisitasProduto(Request $request): JsonResponse
    {
        $visitas = (new PaginaLojaDashboard)->visitasPorProduto($this->lojaAtual($request)->id, 12);

        // o app Angular espera sempre {label, valor}
        return response()->json(array_map(
            fn (array $p) => ['label' => $p['nome'], 'valor' => (float) $p['visitas']],
            $visitas,
        ));
    }
}
