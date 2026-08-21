<?php

namespace App\Pages;

use App\Classes\Login;
use App\Classes\Pessoa;
use App\Interfaces\PagInicial;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\PageVisit;
use App\Models\Product;
use App\Models\ProductClass;
use App\Models\ProductVariant;
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

    /**
     * Catalogo da vitrine com o que os filtros da home precisam alem do produto:
     * quanto ja vendeu, quantas visitas teve e quais tamanhos ainda tem estoque.
     */
    public function vitrine(): Collection
    {
        $produtos = Product::query()
            ->with('subclass')
            ->withSum(['orderItems as quantidade_vendida' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', 'concluido'));
            }], 'quantidade')
            ->orderBy('categoria')
            ->orderBy('nome')
            ->get();

        $tamanhos = ProductVariant::query()
            ->whereNotNull('tamanho')
            ->where('estoque', '>', 0)
            ->select('product_id', 'tamanho')
            ->distinct()
            ->get()
            ->groupBy('product_id')
            ->map(fn ($linhas) => $linhas->pluck('tamanho')->all());

        // mesma leitura do carrosselMaisVisitados: o LogPageVisit so guarda o
        // caminho, entao o id do produto sai de "produtos/<id>". Uma coluna
        // product_id em page_visits evitaria este parsing.
        $visitas = PageVisit::query()
            ->where('path', 'like', 'produtos/%')
            ->select('path', DB::raw('COUNT(*) as visitas'))
            ->groupBy('path')
            ->get()
            ->mapWithKeys(fn ($visita) => [
                (int) str_replace('produtos/', '', $visita->path) => (int) $visita->visitas,
            ]);

        return $produtos->each(function (Product $produto) use ($tamanhos, $visitas) {
            $produto->setAttribute('tamanhos_disponiveis', $tamanhos->get($produto->id, []));
            $produto->setAttribute('visualizacoes', $visitas->get($produto->id, 0));
        });
    }

    /**
     * Estrutura do filtro da home: familia => folhas (subclasses) e grade de
     * tamanhos.
     *
     * A arvore vem das tabelas product_classes/product_subclasses (que
     * espelham app/Classes: Roupa, Calcado, Acessorio e seus subtipos), com
     * uma folha por subclasse que tem pelo menos um produto de fato. Produto
     * ainda sem classe/subclasse (product_subclass_id nulo) cai num bloco
     * "Outros" agrupado pela `categoria` livre, para nao desaparecer do
     * catalogo enquanto nao for reclassificado.
     *
     * O identificador de cada folha (o valor usado para filtrar no front) e
     * o nome da subclasse — ou a propria categoria, para os orfaos — e viaja
     * no JSON de cada produto como `subclasse` (ver $mapParaJs em
     * layouts/app.blade.php) para o app.js casar filtro e produto pela mesma
     * chave.
     */
    public function arvoreDeFiltros(): array
    {
        $classes = ProductClass::with(['subclasses' => function ($query) {
            $query->withCount('products')->orderBy('nome');
        }])->orderBy('nome')->get();

        $arvore = [];

        foreach ($classes as $classe) {
            $folhas = $classe->subclasses
                ->filter(fn ($subclasse) => $subclasse->products_count > 0)
                ->values();

            if ($folhas->isEmpty()) {
                continue;
            }

            $nomes = $folhas->pluck('nome')->all();

            $arvore[] = [
                'familia' => $classe->nome,
                'categorias' => $nomes,
                'folhas' => $nomes,
                'grade' => $this->gradeDeTamanhos(
                    fn ($query) => $query->whereIn('product_subclass_id', $folhas->pluck('id')->all())
                ),
            ];
        }

        $orfas = Product::query()->whereNull('product_subclass_id')->distinct()->pluck('categoria')->filter()->values();

        if ($orfas->isNotEmpty()) {
            $arvore[] = [
                'familia' => 'Outros',
                'categorias' => $orfas->all(),
                'folhas' => $orfas->all(),
                'grade' => $this->gradeDeTamanhos(
                    fn ($query) => $query->whereNull('product_subclass_id')->whereIn('categoria', $orfas->all())
                ),
            ];
        }

        return $arvore;
    }

    /** Tamanhos com estoque nos produtos que passam pelo filtro dado. */
    private function gradeDeTamanhos(\Closure $filtroDeProdutos): array
    {
        $ordem = ['PP', 'P', 'M', 'G', 'GG', 'Único'];

        $tamanhos = ProductVariant::query()
            ->whereNotNull('tamanho')
            ->where('estoque', '>', 0)
            ->whereHas('product', $filtroDeProdutos)
            ->distinct()
            ->pluck('tamanho')
            ->all();

        usort($tamanhos, function ($a, $b) use ($ordem) {
            $posicaoA = array_search($a, $ordem, true);
            $posicaoB = array_search($b, $ordem, true);

            // numero de calcado nao esta na ordem fixa: cai no natural (34 < 35)
            if ($posicaoA === false && $posicaoB === false) {
                return strnatcasecmp($a, $b);
            }

            if ($posicaoA === false) {
                return 1;
            }

            if ($posicaoB === false) {
                return -1;
            }

            return $posicaoA <=> $posicaoB;
        });

        return $tamanhos;
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
            ->with('subclass')
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

        $produtos = Product::whereIn('id', $idsOrdenados)->with('subclass')->get()->keyBy('id');

        return new Collection(
            $idsOrdenados->map(fn ($id) => $produtos->get($id))->filter()->values()->all()
        );
    }

    public function carrosselPromocoes(int $limite = 10): Collection
    {
        return Product::query()
            ->with('subclass')
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
