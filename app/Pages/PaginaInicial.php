<?php

namespace App\Pages;

use App\Classes\Login;
use App\Classes\Pessoa;
use App\Interfaces\PagInicial;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\PageVisit;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaginaInicial implements PagInicial
{
    public function login(string $email, string $senha, bool $lembrar = false): Login
    {
        $tentativa = new Login(email: $email, senha: $senha);

        if (Auth::attempt(['email' => $email, 'password' => $senha], $lembrar)) {
            $user = Auth::user();

            $tentativa->id = $user->id;
            $tentativa->tipoDeUsuario = $user->role;
            $tentativa->ultimoAcesso = now()->toDateTimeString();

            return $tentativa;
        }

        $tentativa->tentativasFalhas++;

        return $tentativa;
    }

    public function filtro(?string $categoria = null): Collection
    {
        return Product::query()
            ->when($categoria, fn ($query) => $query->where('categoria', $categoria))
            ->orderBy('categoria')
            ->orderBy('nome')
            ->get();
    }

    public function busca(string $termo, ?int $userId = null): Collection
    {
        SearchLog::create([
            'termo' => $termo,
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        return Product::query()
            ->where('nome', 'like', "%{$termo}%")
            ->orWhere('descricao', 'like', "%{$termo}%")
            ->orderBy('nome')
            ->get();
    }

    public function notificacao(int $userId): array
    {
        $rotulos = [
            'aguardando_analise' => 'aguardando confirmação do pagamento',
            'aprovado' => 'pagamento aprovado',
            'recusado' => 'pagamento recusado',
        ];
        $rotulosSeparacao = [
            'separado' => 'separado',
            'embalado' => 'embalado',
            'enviado' => 'enviado para entrega',
        ];

        return Order::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function (Order $order) use ($rotulos, $rotulosSeparacao) {
                if ($order->status === 'cancelado') {
                    return "Pedido #{$order->id} foi cancelado.";
                }

                if ($order->status_separacao && isset($rotulosSeparacao[$order->status_separacao])) {
                    return "Pedido #{$order->id}: {$rotulosSeparacao[$order->status_separacao]}.";
                }

                if (isset($rotulos[$order->status_pagamento])) {
                    return "Pedido #{$order->id}: {$rotulos[$order->status_pagamento]}.";
                }

                return "Pedido #{$order->id} está {$order->status}.";
            })
            ->all();
    }

    public function carrinho(int $userId): Collection
    {
        return CartItem::with('product')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function perfil(int $userId): ?Pessoa
    {
        $user = User::find($userId);

        if (! $user) {
            return null;
        }

        return new Pessoa(
            id: $user->id,
            nome: $user->name,
            email: $user->email,
            endereco: $user->endereco,
        );
    }

    public function linkProduto(int $productId): ?Product
    {
        return Product::with(['variants', 'reviews'])->find($productId);
    }

    public function carrosselMaisComprados(int $limite = 10): Collection
    {
        return Product::query()
            ->withSum(['orderItems as quantidade_vendida' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', 'concluido'));
            }], 'quantidade')
            ->having('quantidade_vendida', '>', 0)
            ->orderByDesc('quantidade_vendida')
            ->take($limite)
            ->get();
    }

    public function carrosselMaisVisitados(int $limite = 10): Collection
    {
        $visitas = PageVisit::query()
            ->where('path', 'like', 'produtos/%')
            ->select('path', DB::raw('COUNT(*) as visitas'))
            ->groupBy('path')
            ->orderByDesc('visitas')
            ->limit($limite)
            ->get();

        $idsOrdenados = $visitas
            ->map(fn ($visita) => (int) str_replace('produtos/', '', $visita->path))
            ->filter()
            ->values();

        if ($idsOrdenados->isEmpty()) {
            return new Collection;
        }

        $produtos = Product::whereIn('id', $idsOrdenados)->get()->keyBy('id');

        return new Collection(
            $idsOrdenados->map(fn ($id) => $produtos->get($id))->filter()->values()->all()
        );
    }

    public function carrosselPromocoes(int $limite = 10): Collection
    {
        return Product::query()
            ->whereNotNull('preco_promocional')
            ->whereColumn('preco_promocional', '<', 'preco')
            ->orderBy('nome')
            ->take($limite)
            ->get();
    }

    public function carrosselMaisAvaliados(int $limite = 10): Collection
    {
        return Product::query()
            ->withAvg('reviews', 'avaliacao')
            ->withCount('reviews')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_avg_avaliacao')
            ->take($limite)
            ->get();
    }

    public function carrosselPremium(int $limite = 10): Collection
    {
        return Product::query()
            ->whereHas('loja', fn ($query) => $query->whereNotNull('plano_id'))
            ->take($limite)
            ->get();
    }
}
