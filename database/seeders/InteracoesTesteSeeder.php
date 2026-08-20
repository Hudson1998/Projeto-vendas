<?php

namespace Database\Seeders;

use App\Models\CartItem;
use App\Models\Favorite;
use App\Models\PageVisit;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Interacoes dos clientes de teste com a loja: favoritos, avaliacoes,
 * buscas, carrinhos em aberto e visitas a paginas de produto.
 *
 * Depende de TestDataSeeder (clientes) e CatalogoTesteSeeder (produtos e
 * variantes). Rode com `php artisan db:seed --class=InteracoesTesteSeeder`.
 */
class InteracoesTesteSeeder extends Seeder
{
    private const EMAILS = [
        'ana@teste.com',
        'beatriz@teste.com',
        'carla@teste.com',
    ];

    /** Marca as visitas criadas aqui para poder remove-las ao reexecutar. */
    private const AGENTE_SEED = 'Mozilla/5.0 (seed de teste)';

    /** [termo, email do autor ou null se anonimo, dias atras] */
    private const BUSCAS = [
        ['vestido preto', 'ana@teste.com', 27],
        ['blusa de tricô', 'ana@teste.com', 21],
        ['scarpin', 'ana@teste.com', 12],
        ['camisa social', 'beatriz@teste.com', 25],
        ['blazer', 'beatriz@teste.com', 18],
        ['tênis branco', 'beatriz@teste.com', 7],
        ['saia plissada', 'carla@teste.com', 23],
        ['vestido longo', 'carla@teste.com', 16],
        ['relógio', 'carla@teste.com', 4],
        ['bolsa', null, 20],
        ['sandália', null, 14],
        ['vestido', null, 9],
        ['óculos de sol', null, 3],
        ['calça pantalona', null, 2],
    ];

    public function run(): void
    {
        $clientes = User::whereIn('email', self::EMAILS)->get()->keyBy('email');
        $produtos = Product::with('variants')->get()->keyBy('nome');

        if ($clientes->isEmpty() || $produtos->isEmpty()) {
            return;
        }

        $this->limpar($clientes->pluck('id')->all());

        $this->criarFavoritos($clientes, $produtos);
        $this->criarAvaliacoes($clientes, $produtos);
        $this->criarCarrinhos($clientes, $produtos);
        $this->criarBuscas($clientes);
        $this->criarVisitas($clientes, $produtos);
    }

    /** Favoritos ("likes") por cliente. */
    private function criarFavoritos(Collection $clientes, Collection $produtos): void
    {
        $mapa = [
            'ana@teste.com' => ['Vestido Midi Preto', 'Blusa de Tricô Canelada', 'Scarpin Salto Fino', 'Colar Corrente Fina', 'Casaco Trench Bege'],
            'beatriz@teste.com' => ['Camisa Alfaiataria', 'Blazer Oversized', 'Tênis Branco Minimalista', 'Óculos de Sol Redondo'],
            'carla@teste.com' => ['Vestido Longo Cetim', 'Saia Plissada', 'Blusa Cropped Básica', 'Relógio Dourado Slim', 'Bota Cano Curto', 'Brinco Argola Pequena'],
        ];

        foreach ($mapa as $email => $nomes) {
            $cliente = $clientes->get($email);

            foreach ($nomes as $nome) {
                $produto = $produtos->get($nome);

                if (! $cliente || ! $produto) {
                    continue;
                }

                Favorite::updateOrCreate([
                    'user_id' => $cliente->id,
                    'product_id' => $produto->id,
                ]);
            }
        }
    }

    /** Avaliacoes com nota e comentario (uma por cliente/produto). */
    private function criarAvaliacoes(Collection $clientes, Collection $produtos): void
    {
        // [email, produto, nota, comentario]
        $avaliacoes = [
            ['ana@teste.com', 'Vestido Midi Preto', 5, 'Caimento impecável e o tecido não amassa. Comprei o M e serviu certinho.'],
            ['ana@teste.com', 'Cinto Fivela Dourada', 4, 'Fivela bonita, mas o couro é mais rígido do que eu esperava.'],
            ['ana@teste.com', 'Bolsa Estruturada', 5, 'Cabe notebook e não deforma. Melhor compra do mês.'],
            ['beatriz@teste.com', 'Camisa Alfaiataria', 4, 'Ótimo acabamento. Só achei a manga um pouco longa.'],
            ['beatriz@teste.com', 'Vestido Midi Preto', 3, 'Bonito, mas o tecido marca mais do que aparenta na foto.'],
            ['beatriz@teste.com', 'Calça Pantalona', 5, 'Cintura alta perfeita, alonga muito a silhueta.'],
            ['carla@teste.com', 'Vestido Midi Preto', 5, 'Usei em casamento e recebi vários elogios. Recomendo.'],
            ['carla@teste.com', 'Saia Plissada', 4, 'O plissado mantém a forma depois da lavagem.'],
            ['carla@teste.com', 'Vestido Longo Cetim', 5, 'O cetim é pesado, de qualidade. Vale o preço.'],
        ];

        foreach ($avaliacoes as [$email, $nome, $nota, $comentario]) {
            $cliente = $clientes->get($email);
            $produto = $produtos->get($nome);

            if (! $cliente || ! $produto) {
                continue;
            }

            ProductReview::updateOrCreate(
                ['user_id' => $cliente->id, 'product_id' => $produto->id],
                ['avaliacao' => $nota, 'comentario' => $comentario]
            );
        }
    }

    /**
     * Itens deixados no carrinho, com tamanho e cor tirados das variantes
     * reais para o carrinho conseguir casar com o estoque.
     */
    private function criarCarrinhos(Collection $clientes, Collection $produtos): void
    {
        $mapa = [
            'ana@teste.com' => [['Blazer Oversized', 1], ['Brinco Argola Pequena', 2]],
            'beatriz@teste.com' => [['Sandália Tira Fina', 1]],
            'carla@teste.com' => [['Blusa de Linho Manga Bufante', 1], ['Óculos de Sol Redondo', 1]],
        ];

        foreach ($mapa as $email => $itens) {
            $cliente = $clientes->get($email);

            foreach ($itens as [$nome, $quantidade]) {
                $produto = $produtos->get($nome);

                if (! $cliente || ! $produto) {
                    continue;
                }

                // primeira variante com saldo, para o item nao nascer esgotado
                $variante = $produto->variants->firstWhere('estoque', '>', 0)
                    ?? $produto->variants->first();

                CartItem::updateOrCreate(
                    ['user_id' => $cliente->id, 'product_id' => $produto->id],
                    [
                        'quantidade' => $quantidade,
                        'tamanho' => $variante?->tamanho,
                        'cor' => $variante?->cor,
                    ]
                );
            }
        }
    }

    /** Buscas registradas, incluindo visitantes nao logados. */
    private function criarBuscas(Collection $clientes): void
    {
        foreach (self::BUSCAS as [$termo, $email, $dias]) {
            SearchLog::create([
                'termo' => $termo,
                'user_id' => $email ? $clientes->get($email)?->id : null,
                'created_at' => now()->subDays($dias),
            ]);
        }
    }

    /**
     * Visitas a paginas de produto. O carrossel "mais visitados" le
     * page_visits com path "produtos/{id}", entao os pesos abaixo definem
     * o ranking que aparece na home.
     */
    private function criarVisitas(Collection $clientes, Collection $produtos): void
    {
        // produto => quantas visitas gerar
        $pesos = [
            'Vestido Midi Preto' => 18,
            'Blusa de Tricô Canelada' => 14,
            'Tênis Branco Minimalista' => 12,
            'Casaco Trench Bege' => 9,
            'Scarpin Salto Fino' => 8,
            'Camisa Alfaiataria' => 7,
            'Vestido Longo Cetim' => 6,
            'Saia Plissada' => 5,
            'Óculos de Sol Redondo' => 4,
            'Bolsa Estruturada' => 3,
        ];

        $usuarios = $clientes->values();
        $contador = 0;

        foreach ($pesos as $nome => $total) {
            $produto = $produtos->get($nome);

            if (! $produto) {
                continue;
            }

            for ($i = 0; $i < $total; $i++) {
                // parte das visitas fica anonima, como trafego real
                $usuario = $contador % 3 === 0 ? null : $usuarios[$contador % $usuarios->count()];

                PageVisit::create([
                    'path' => "produtos/{$produto->id}",
                    'ip_address' => '192.0.2.'.(10 + ($contador % 40)),
                    'user_agent' => self::AGENTE_SEED,
                    'user_id' => $usuario?->id,
                    'created_at' => now()->subDays($contador % 30)->subHours($contador % 24),
                ]);

                $contador++;
            }
        }
    }

    /**
     * @param  array<int, int>  $clienteIds
     */
    private function limpar(array $clienteIds): void
    {
        Favorite::whereIn('user_id', $clienteIds)->delete();
        ProductReview::whereIn('user_id', $clienteIds)->delete();
        CartItem::whereIn('user_id', $clienteIds)->delete();

        // search_logs nao tem coluna de marcacao, entao removemos pelos termos
        // que este seeder cria (inclui os anonimos, que nao tem user_id)
        SearchLog::whereIn('termo', array_column(self::BUSCAS, 0))->delete();

        PageVisit::where('user_agent', self::AGENTE_SEED)->delete();
    }
}
