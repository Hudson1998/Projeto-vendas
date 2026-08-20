<?php

namespace App\Pages;

use App\Interfaces\LojaDashboard;
use App\Models\OrderItem;
use App\Models\PageVisit;
use App\Models\Product;
use App\Models\User;
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
}
