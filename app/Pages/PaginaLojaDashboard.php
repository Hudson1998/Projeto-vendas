<?php

namespace App\Pages;

use App\Interfaces\LojaDashboard;
use App\Models\OrderItem;
use App\Models\PageVisit;
use App\Models\Product;
use App\Models\User;
use App\Support\CarteiraDaLoja;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

class PaginaLojaDashboard implements LojaDashboard
{
    public function visitantes(int $lojaId): Collection
    {
        $produtoIds = Product::where('loja_id', $lojaId)->pluck('id');
        $paths = $produtoIds->map(fn ($id) => "produtos/{$id}");

        $userIds = PageVisit::whereIn('path', $paths)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }

    public function vendasPorDia(int $lojaId, int $dias = 14): BaseCollection
    {
        $inicio = now()->subDays($dias - 1)->startOfDay();

        $vendas = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.loja_id', $lojaId)
            ->where('orders.status', '!=', 'cancelado')
            ->where('orders.created_at', '>=', $inicio)
            ->selectRaw('DATE(orders.created_at) as dia, SUM(order_items.quantidade * order_items.preco_unitario) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        return collect(range(0, $dias - 1))->map(function ($i) use ($vendas, $dias) {
            $dia = now()->subDays($dias - 1 - $i)->format('Y-m-d');

            return ['dia' => $dia, 'total' => (float) ($vendas[$dia] ?? 0)];
        });
    }

    public function visitasPorDia(int $lojaId, int $dias = 14): BaseCollection
    {
        $produtoIds = Product::where('loja_id', $lojaId)->pluck('id');
        $paths = $produtoIds->map(fn ($id) => "produtos/{$id}");
        $inicio = now()->subDays($dias - 1)->startOfDay();

        $visitas = PageVisit::whereIn('path', $paths)
            ->where('created_at', '>=', $inicio)
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        return collect(range(0, $dias - 1))->map(function ($i) use ($visitas, $dias) {
            $dia = now()->subDays($dias - 1 - $i)->format('Y-m-d');

            return ['dia' => $dia, 'total' => (int) ($visitas[$dia] ?? 0)];
        });
    }

    public function produtosMaisVendidos(int $lojaId, int $limite = 10): Collection
    {
        return Product::query()
            ->where('loja_id', $lojaId)
            ->withSum(['orderItems as quantidade_vendida' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', 'concluido'));
            }], 'quantidade')
            ->having('quantidade_vendida', '>', 0)
            ->orderByDesc('quantidade_vendida')
            ->take($limite)
            ->get();
    }

    public function produtosMaisVisitados(int $lojaId, int $limite = 10): Collection
    {
        $visitas = PageVisit::query()
            ->where('path', 'like', 'produtos/%')
            ->select('path', DB::raw('COUNT(*) as visitas'))
            ->groupBy('path')
            ->pluck('visitas', 'path');

        return Product::where('loja_id', $lojaId)
            ->get()
            ->map(fn ($produto) => [
                'produto' => $produto,
                'visitas' => $visitas["produtos/{$produto->id}"] ?? 0,
            ])
            ->filter(fn ($item) => $item['visitas'] > 0)
            ->sortByDesc('visitas')
            ->take($limite)
            ->pluck('produto')
            ->values()
            ->pipe(fn ($items) => new Collection($items->all()));
    }

    /**
     * Lucro liquido da loja no periodo, ja sem a comissao da plataforma.
     *
     * "Lucro" aqui e o que sobra para a loja, nao margem sobre custo: o
     * sistema nao conhece o custo das pecas. Frete fica de fora -- ele paga a
     * entrega, nao a loja.
     *
     * @return array<int, array{label: string, valor: float}>
     */
    public function lucroPorPeriodo(int $lojaId, string $granularidade = 'dia'): array
    {
        $consulta = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('products.loja_id', $lojaId)
            ->where('orders.status', '!=', 'cancelado')
            ->whereIn('orders.status_pagamento', ['aprovado', 'aguardando_analise']);

        $liquido = 1 - CarteiraDaLoja::COMISSAO_PADRAO / 100;

        return $this->serie(
            $consulta,
            $granularidade,
            'orders.created_at',
            'SUM(order_items.quantidade * order_items.preco_unitario)',
            fn (float $bruto) => round($bruto * $liquido, 2),
        );
    }

    /**
     * Visitantes das paginas dos produtos da loja no periodo.
     *
     * Conta visitas, nao pessoas distintas: distinguir visitante exigiria
     * agrupar por user_id dentro de cada intervalo, e visita anonima nao tem
     * user_id nenhum. O total de pessoas unicas vem de visitantes().
     *
     * @return array<int, array{label: string, valor: float}>
     */
    public function visitantesPorPeriodo(int $lojaId, string $granularidade = 'dia'): array
    {
        $paths = Product::where('loja_id', $lojaId)->pluck('id')->map(fn ($id) => "produtos/{$id}");

        return $this->serie(
            PageVisit::whereIn('path', $paths),
            $granularidade,
            'created_at',
            'COUNT(*)',
        );
    }

    /**
     * Visitas acumuladas por produto da loja.
     *
     * @return array<int, array{id: int, nome: string, visitas: int}>
     */
    public function visitasPorProduto(int $lojaId, int $limite = 12): array
    {
        $produtos = Product::where('loja_id', $lojaId)->get(['id', 'nome']);

        $visitas = PageVisit::query()
            ->whereIn('path', $produtos->map(fn ($p) => "produtos/{$p->id}"))
            ->selectRaw('path, COUNT(*) as total')
            ->groupBy('path')
            ->pluck('total', 'path');

        return $produtos
            ->map(fn ($p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'visitas' => (int) ($visitas["produtos/{$p->id}"] ?? 0),
            ])
            ->sortByDesc('visitas')
            ->take($limite)
            ->values()
            ->all();
    }

    /**
     * As vendas da loja, peca a peca, para a tabela do painel.
     */
    public function tabelaDeVendas(int $lojaId, int $limite = 40): BaseCollection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('products.loja_id', $lojaId)
            ->where('orders.status', '!=', 'cancelado')
            ->orderByDesc('orders.created_at')
            ->limit($limite)
            ->get([
                'orders.id as pedido_id',
                'orders.created_at as data',
                'orders.status_pagamento',
                'orders.status_separacao',
                'users.name as cliente',
                'products.nome as produto',
                'order_items.quantidade',
                'order_items.preco_unitario',
            ])
            ->map(function ($linha) {
                $bruto = (float) $linha->quantidade * (float) $linha->preco_unitario;

                return [
                    'pedido_id' => $linha->pedido_id,
                    'data' => $linha->data,
                    'cliente' => $linha->cliente,
                    'produto' => $linha->produto,
                    'quantidade' => (int) $linha->quantidade,
                    'bruto' => round($bruto, 2),
                    'liquido' => round($bruto * (1 - CarteiraDaLoja::COMISSAO_PADRAO / 100), 2),
                    'status_pagamento' => $linha->status_pagamento,
                    'status_separacao' => $linha->status_separacao,
                ];
            });
    }

    /**
     * Serie temporal agrupada por hora (dia), dia (mes) ou mes (ano).
     *
     * Mesma forma que os graficos do admin usam, para o app Angular consumir
     * os dois sem saber de qual painel veio.
     *
     * @param  callable|null  $transformar  ajuste final de cada valor
     * @return array<int, array{label: string, valor: float}>
     */
    private function serie($consulta, string $granularidade, string $coluna, string $agregacao, ?callable $transformar = null): array
    {
        $agora = now();

        if ($granularidade === 'mes') {
            $consulta->whereYear($coluna, $agora->year)->whereMonth($coluna, $agora->month);
            $grupo = "DAY($coluna)";
            $faixa = range(1, $agora->daysInMonth);
            $rotulo = fn ($i) => sprintf('%02d', $i);
        } elseif ($granularidade === 'ano') {
            $consulta->whereYear($coluna, $agora->year);
            $grupo = "MONTH($coluna)";
            $faixa = range(1, 12);
            $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            $rotulo = fn ($i) => $meses[$i - 1];
        } else {
            $consulta->whereDate($coluna, $agora->toDateString());
            $grupo = "HOUR($coluna)";
            $faixa = range(0, 23);
            $rotulo = fn ($i) => sprintf('%02dh', $i);
        }

        $dados = $consulta
            ->selectRaw("$grupo as chave, $agregacao as total")
            ->groupBy('chave')
            ->pluck('total', 'chave');

        return collect($faixa)->map(function ($i) use ($dados, $rotulo, $transformar) {
            $valor = (float) ($dados[$i] ?? 0);

            return [
                'label' => $rotulo($i),
                'valor' => $transformar ? $transformar($valor) : $valor,
            ];
        })->values()->all();
    }
}
