<?php

namespace App\Http\Controllers;

use App\Classes\Loja;
use App\Models\Order;
use App\Pages\PaginaAnalise;
use App\Pages\PaginaLojaDashboard;
use App\Pages\PaginaTransportadora;
use App\Support\ImagemDePerfil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LojaDashboardController extends Controller
{
    private function lojaAtual(Request $request): Loja
    {
        return $request->user()->loja ?? abort(404);
    }

    /**
     * Perfil publico da loja: o que o comprador ve na vitrine (/lojas/{id})
     * e no bloco "Vendido por" da pagina do produto.
     */
    public function perfil(Request $request): View
    {
        return view('loja.perfil', ['loja' => $this->lojaAtual($request)]);
    }

    public function atualizarPerfil(Request $request): RedirectResponse
    {
        $loja = $this->lojaAtual($request);

        $data = $request->validate([
            'nome_exibicao' => ['nullable', 'string', 'max:255'],
            'bio_loja' => ['required', 'string', 'max:2000'],
            'logotipo' => ImagemDePerfil::regras(),
            'remover_logotipo' => ['nullable', 'boolean'],
        ]);

        // o campo do banco guarda o caminho, nao o arquivo enviado
        unset($data['logotipo'], $data['remover_logotipo']);

        if ($request->hasFile('logotipo')) {
            $data['logotipo'] = ImagemDePerfil::guardar($request->file('logotipo'), 'logo_', $loja->logotipo);
        } elseif ($request->boolean('remover_logotipo')) {
            ImagemDePerfil::apagar($loja->logotipo);
            $data['logotipo'] = null;
        }

        $loja->update($data);

        return back()->with('status', 'Perfil da loja atualizado com sucesso!');
    }

    public function dashboard(Request $request): View
    {
        $loja = $this->lojaAtual($request);
        $painel = new PaginaLojaDashboard;

        return view('loja.dashboard', [
            'loja' => $loja,
            'vendasPorDia' => $painel->vendasPorDia($loja->id),
            'visitasPorDia' => $painel->visitasPorDia($loja->id),
            'produtosMaisVendidos' => $painel->produtosMaisVendidos($loja->id, 5),
            'produtosMaisVisitados' => $painel->produtosMaisVisitados($loja->id, 5),
            'totalVisitantes' => $painel->visitantes($loja->id)->count(),
        ]);
    }

    public function dados(Request $request): JsonResponse
    {
        $loja = $this->lojaAtual($request);
        $painel = new PaginaLojaDashboard;

        return response()->json([
            'vendasPorDia' => $painel->vendasPorDia($loja->id),
            'visitasPorDia' => $painel->visitasPorDia($loja->id),
        ]);
    }

    public function pedidos(Request $request): View
    {
        $loja = $this->lojaAtual($request);

        $pedidos = Order::with('user', 'items.product', 'transportadora', 'motorista')
            ->whereHas('items.product', fn ($q) => $q->where('loja_id', $loja->id))
            ->where('status_pagamento', 'aprovado')
            ->latest()
            ->get();

        return view('loja.pedidos', [
            'loja' => $loja,
            'pedidos' => $pedidos,
            'transportadoras' => (new PaginaTransportadora)->listarDisponiveis($loja->id),
        ]);
    }

    private function autorizarPedido(Request $request, Order $order): void
    {
        $loja = $this->lojaAtual($request);

        abort_unless(
            $order->items()->whereHas('product', fn ($q) => $q->where('loja_id', $loja->id))->exists(),
            403
        );
    }

    public function definirEntrega(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        $data = $request->validate([
            'entrega_propria' => ['required', 'boolean'],
            'transportadora_id' => ['nullable', 'exists:transportadoras,id'],
            'motorista_id' => ['nullable', 'exists:motoristas,id'],
        ]);

        (new PaginaAnalise)->escolherEntrega(
            $order,
            $data['entrega_propria'],
            $data['transportadora_id'] ?? null,
            $data['motorista_id'] ?? null,
        );

        return back()->with('status', 'Forma de entrega definida para o pedido #'.$order->id.'.');
    }

    public function separar(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        (new PaginaAnalise)->separar($order);

        return back()->with('status', 'Pedido #'.$order->id.' marcado como separado.');
    }

    public function embalar(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        $data = $request->validate([
            'fragil' => ['sometimes', 'boolean'],
            'dimensoes' => ['nullable', 'string', 'max:255'],
        ]);

        $analise = new PaginaAnalise;
        $analise->embalagem($order);
        $analise->fragil($order, $request->boolean('fragil'));

        if (! empty($data['dimensoes'])) {
            $analise->dimensao($order, $data['dimensoes']);
        }

        return back()->with('status', 'Pedido #'.$order->id.' embalado.');
    }

    public function enviar(Request $request, Order $order): RedirectResponse
    {
        $this->autorizarPedido($request, $order);

        (new PaginaAnalise)->enviar($order);

        return back()->with('status', 'Pedido #'.$order->id.' despachado.');
    }

    public function transportadoras(Request $request): View
    {
        $loja = $this->lojaAtual($request);
        $paginaTransportadora = new PaginaTransportadora;

        return view('loja.transportadoras', [
            'loja' => $loja,
            'vinculadas' => $loja->transportadoras()->get(),
            'disponiveis' => \App\Classes\Transportadora::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function cadastrarTransportadora(Request $request): RedirectResponse
    {
        $loja = $this->lojaAtual($request);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'tipo_veiculo' => ['nullable', 'string', 'max:255'],
            'area_atuacao' => ['nullable', 'string', 'max:255'],
        ]);

        $paginaTransportadora = new PaginaTransportadora;
        $transportadora = $paginaTransportadora->cadastrarTransportadora($data);
        $paginaTransportadora->vincularLoja($loja->id, $transportadora->id);

        return back()->with('status', 'Transportadora cadastrada e vinculada à sua loja.');
    }

    public function vincular(Request $request, \App\Classes\Transportadora $transportadora): RedirectResponse
    {
        $loja = $this->lojaAtual($request);

        (new PaginaTransportadora)->vincularLoja($loja->id, $transportadora->id);

        return back()->with('status', 'Transportadora "'.$transportadora->nome.'" vinculada.');
    }

    public function desvincular(Request $request, \App\Classes\Transportadora $transportadora): RedirectResponse
    {
        $loja = $this->lojaAtual($request);

        (new PaginaTransportadora)->desvincularLoja($loja->id, $transportadora->id);

        return back()->with('status', 'Transportadora "'.$transportadora->nome.'" desvinculada.');
    }

    public function cadastrarMotorista(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'transportadora_id' => ['nullable', 'exists:transportadoras,id'],
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'cnh' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ]);

        (new PaginaTransportadora)->cadastrarMotorista($data);

        return back()->with('status', 'Motorista cadastrado com sucesso.');
    }

    public function clientes(Request $request): View
    {
        $loja = $this->lojaAtual($request);

        return view('loja.clientes', [
            'loja' => $loja,
            'visitantes' => (new PaginaLojaDashboard)->visitantes($loja->id),
        ]);
    }

    public function produtos(Request $request): View
    {
        $loja = $this->lojaAtual($request);
        $painel = new PaginaLojaDashboard;

        return view('loja.produtos', [
            'loja' => $loja,
            'produtos' => $loja->produtos()->with('variants')->get(),
            'maisVendidos' => $painel->produtosMaisVendidos($loja->id, 10),
            'maisVisitados' => $painel->produtosMaisVisitados($loja->id, 10),
        ]);
    }
}
