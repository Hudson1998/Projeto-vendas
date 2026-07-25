<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PageVisit;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', $this->buildStats());
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->buildStats());
    }

    public function charts(): View
    {
        return view('admin.charts');
    }

    public function acessos(Request $request): JsonResponse
    {
        $granularidade = $request->query('granularidade', 'dia');

        return response()->json(
            $this->serieTemporal(PageVisit::query(), $granularidade)
        );
    }

    public function receita(Request $request): JsonResponse
    {
        $granularidade = $request->query('granularidade', 'dia');

        return response()->json(
            $this->serieTemporal(Order::where('status', '!=', 'cancelado'), $granularidade, 'sum', 'total')
        );
    }

    public function volumeCompras(): JsonResponse
    {
        $dias = collect(range(13, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $contagem = Order::where('status', '!=', 'cancelado')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        return response()->json($dias->map(fn ($d) => [
            'label' => Carbon::parse($d)->format('d/m'),
            'valor' => (int) ($contagem[$d] ?? 0),
        ])->values());
    }

    public function satisfacao(): JsonResponse
    {
        $distribuicao = Order::whereNotNull('avaliacao')
            ->selectRaw('avaliacao, COUNT(*) as total')
            ->groupBy('avaliacao')
            ->pluck('total', 'avaliacao');

        return response()->json([
            'media' => round((float) Order::whereNotNull('avaliacao')->avg('avaliacao'), 2),
            'totalAvaliacoes' => Order::whereNotNull('avaliacao')->count(),
            'distribuicao' => collect(range(1, 5))->map(fn ($i) => [
                'label' => $i.' estrela'.($i > 1 ? 's' : ''),
                'valor' => (int) ($distribuicao[$i] ?? 0),
            ])->values(),
        ]);
    }

    public function vendasPorCategoria(): JsonResponse
    {
        $dados = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelado')
            ->select('products.categoria as label', DB::raw('sum(order_items.quantidade) as valor'))
            ->groupBy('products.categoria')
            ->orderByDesc('valor')
            ->get();

        return response()->json($dados);
    }

    private function serieTemporal($query, string $granularidade, string $agregacao = 'count', ?string $coluna = null): array
    {
        $now = now();

        if ($granularidade === 'mes') {
            $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
            $groupExpr = 'DAY(created_at)';
            $range = range(1, $now->daysInMonth);
            $labelFn = fn ($i) => sprintf('%02d', $i);
        } elseif ($granularidade === 'ano') {
            $query->whereYear('created_at', $now->year);
            $groupExpr = 'MONTH(created_at)';
            $range = range(1, 12);
            $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            $labelFn = fn ($i) => $meses[$i - 1];
        } else {
            $query->whereDate('created_at', $now->toDateString());
            $groupExpr = 'HOUR(created_at)';
            $range = range(0, 23);
            $labelFn = fn ($i) => sprintf('%02dh', $i);
        }

        $selectAgg = $agregacao === 'sum' ? "SUM($coluna)" : 'COUNT(*)';

        $dados = $query
            ->selectRaw("$groupExpr as chave, $selectAgg as total")
            ->groupBy('chave')
            ->pluck('total', 'chave');

        return collect($range)->map(fn ($i) => [
            'label' => $labelFn($i),
            'valor' => (float) ($dados[$i] ?? 0),
        ])->values()->all();
    }

    public function clientes(): View
    {
        $clientes = User::where('role', 'cliente')
            ->withCount('orders')
            ->withSum(['orders as total_gasto' => fn ($q) => $q->where('status', 'concluido')], 'total')
            ->latest()
            ->get();

        return view('admin.clientes', ['clientes' => $clientes]);
    }

    public function produtos(): View
    {
        $produtos = Product::with('variants')->orderBy('categoria')->orderBy('nome')->get();

        return view('admin.produtos', ['produtos' => $produtos]);
    }

    public function emails(): View
    {
        $clientes = User::where('role', 'cliente')
            ->orderBy('name')
            ->get(['name', 'email']);

        return view('admin.emails', ['clientes' => $clientes]);
    }

    public function faturamento(): View
    {
        return view('admin.faturamento', $this->buildFaturamento());
    }

    public function faturamentoDados(): JsonResponse
    {
        return response()->json($this->buildFaturamento());
    }

    public function atualizarConfiguracoes(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'custo_logistica_padrao' => ['required', 'numeric', 'min:0'],
        ]);

        Setting::set('custo_logistica_padrao', $data['custo_logistica_padrao']);

        return back()->with('status', 'Configuração de logística atualizada.');
    }

    private function buildFaturamento(): array
    {
        $custoLogisticaPadrao = (float) Setting::get('custo_logistica_padrao', 0);
        $pedidosConcluidos = Order::where('status', 'concluido');

        $entradaPeriodo = function (?Carbon $inicio = null) use ($pedidosConcluidos) {
            $query = clone $pedidosConcluidos;
            if ($inicio) {
                $query->where('created_at', '>=', $inicio);
            }

            return (float) ($query->sum('total') ?? 0);
        };

        $pedidosCountPeriodo = function (?Carbon $inicio = null) use ($pedidosConcluidos) {
            $query = clone $pedidosConcluidos;
            if ($inicio) {
                $query->where('created_at', '>=', $inicio);
            }

            return $query->count();
        };

        $saidaPeriodo = fn (?Carbon $inicio = null) => $custoLogisticaPadrao * $pedidosCountPeriodo($inicio);

        $hoje = now()->startOfDay();
        $inicioMes = now()->startOfMonth();
        $inicioAno = now()->startOfYear();

        return [
            'ganhosDiarios' => round($entradaPeriodo($hoje) - $saidaPeriodo($hoje), 2),
            'ganhosMensais' => round($entradaPeriodo($inicioMes) - $saidaPeriodo($inicioMes), 2),
            'ganhosAnuais' => round($entradaPeriodo($inicioAno) - $saidaPeriodo($inicioAno), 2),
        ];
    }

    private function buildStats(): array
    {
        $hoje = now()->startOfDay();

        return [
            'totalVisitas' => PageVisit::count(),
            'visitasHoje' => PageVisit::where('created_at', '>=', $hoje)->count(),
            'visitantesUnicos' => PageVisit::distinct('ip_address')->count('ip_address'),
            'totalCadastrados' => User::where('role', 'cliente')->count(),
            'cadastrosHoje' => User::where('role', 'cliente')->where('created_at', '>=', $hoje)->count(),
            'totalPedidos' => Order::count(),
            'ultimosCadastros' => User::where('role', 'cliente')
                ->latest()
                ->limit(8)
                ->get(['name', 'email', 'created_at'])
                ->map(fn ($u) => [
                    'nome' => $u->name,
                    'email' => $u->email,
                    'data' => $u->created_at->format('d/m/Y H:i'),
                ]),
            'ultimasVisitas' => PageVisit::with('user:id,name')
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($v) => [
                    'caminho' => $v->path === '/' ? '/' : '/'.$v->path,
                    'ip' => $v->ip_address,
                    'usuario' => $v->user?->name,
                    'data' => $v->created_at->format('d/m/Y H:i:s'),
                ]),
            'termosMaisBuscados' => SearchLog::select('termo', DB::raw('count(*) as total'))
                ->groupBy('termo')
                ->orderByDesc('total')
                ->limit(8)
                ->get(),
            'produtosMaisVendidos' => DB::table('order_items')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelado')
                ->select('products.nome', DB::raw('sum(order_items.quantidade) as total_vendido'))
                ->groupBy('products.id', 'products.nome')
                ->orderByDesc('total_vendido')
                ->limit(8)
                ->get(),
        ];
    }
}
