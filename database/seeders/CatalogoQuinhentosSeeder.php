<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Leva o catalogo a 500 produtos, cada um com estoque em product_variants.
 *
 * As imagens vem de public/assets/catalogo/<categoria>/, baixadas do acervo
 * aberto do Metropolitan Museum (CC0). A pasta e lida em tempo de execucao:
 * o seeder usa quantas imagens existirem, repetindo-as se forem menos que os
 * produtos daquela categoria, e cai nas fotos originais se a pasta faltar.
 *
 * Nomes sao deterministicos, entao reexecutar nao duplica nada.
 *
 * Rode com `php artisan db:seed --class=CatalogoQuinhentosSeeder`.
 */
class CatalogoQuinhentosSeeder extends Seeder
{
    private const ALVO = 500;

    private const PASTA_IMAGENS = 'assets/catalogo';

    /** categoria => [pasta de imagens, faixa de preco, quantos produtos] */
    private const PLANO = [
        'Vestidos' => ['vestidos', 149, 459, 80],
        'Blusas' => ['blusas', 69, 219, 70],
        'Camisas' => ['camisas', 89, 259, 55],
        'Saias' => ['saias', 99, 289, 55],
        'Calças' => ['calcas', 119, 329, 55],
        'Casacos' => ['casacos', 199, 699, 60],
        'Calçados' => ['calcados', 129, 499, 65],
        'Acessórios' => ['acessorios', 39, 349, 60],
    ];

    private const MODELOS = [
        'Vestidos' => ['Midi', 'Longo', 'Tubinho', 'Envelope', 'Chemise', 'Godê', 'Reto', 'Transpassado', 'Frente Única', 'Trapézio'],
        'Blusas' => ['Cropped', 'Canelada', 'Manga Bufante', 'Gola Alta', 'Ombro a Ombro', 'Regata', 'Amarração', 'Solta'],
        'Camisas' => ['Alfaiataria', 'Oversized', 'Manga Curta', 'Gola Padre', 'Listrada', 'Slim', 'Botões Forrados'],
        'Saias' => ['Lápis', 'Plissada', 'Midi', 'Longa', 'Evasê', 'Envelope', 'Com Fenda'],
        'Calças' => ['Pantalona', 'Reta', 'Skinny', 'Cargo', 'Clochard', 'Flare', 'Cropped'],
        'Casacos' => ['Trench', 'Blazer', 'Parka', 'Bomber', 'Sobretudo', 'Cardigã', 'Jaqueta', 'Capa'],
        'Calçados' => ['Scarpin', 'Tênis', 'Bota', 'Sandália', 'Mocassim', 'Rasteira', 'Ankle Boot', 'Mule'],
        'Acessórios' => ['Bolsa', 'Cinto', 'Colar', 'Brinco', 'Óculos', 'Relógio', 'Lenço', 'Pulseira', 'Chapéu'],
    ];

    private const ACABAMENTOS = [
        'Cetim', 'Linho', 'Tricô', 'Malha', 'Jeans', 'Couro', 'Veludo', 'Seda',
        'Algodão', 'Crepe', 'Sarja', 'Renda', 'Tweed', 'Camurça', 'Viscose',
        'Musseline', 'Jacquard', 'Lã', 'Chiffon', 'Popeline',
    ];

    private const TONS = [
        'Preto', 'Off-White', 'Bege', 'Camel', 'Vinho', 'Marinho', 'Verde Musgo',
        'Terracota', 'Cinza', 'Areia', 'Chumbo', 'Ferrugem', 'Oliva', 'Marfim',
    ];

    private const GRADE_ROUPA = ['PP', 'P', 'M', 'G', 'GG'];

    private const GRADE_CALCADO = ['34', '35', '36', '37', '38', '39', '40'];

    /** Usada enquanto a galeria da categoria ainda nao foi baixada. */
    private const IMAGEM_PENDENTE = self::PASTA_IMAGENS.'/_pendente.jpg';

    public function run(): void
    {
        $existentes = DB::table('products')->count();

        if ($existentes >= self::ALVO) {
            $this->command?->info("Catalogo ja tem {$existentes} produtos.");
        } else {
            $this->criarProdutos();
        }

        $this->criarEstoque();

        // passo separado de proposito: o download das fotos e lento, entao da
        // para semear antes e reexecutar so este seeder depois para atualizar
        // as imagens conforme a galeria cresce
        $this->atribuirImagens();
    }

    /**
     * Redistribui as fotos da galeria entre os produtos gerados.
     *
     * Mexe apenas em quem aponta para assets/catalogo/, preservando as imagens
     * curadas dos produtos originais do projeto.
     */
    private function atribuirImagens(): void
    {
        $galerias = $this->imagensPorCategoria();

        if ($galerias === []) {
            return;
        }

        // caminhos completos, por pasta e num conjunto unico
        $porPasta = [];
        $conjunto = [];

        foreach ($galerias as $pasta => $arquivos) {
            $caminhos = array_map(
                fn ($a) => self::PASTA_IMAGENS.'/'.$pasta.'/'.$a,
                $arquivos
            );

            $porPasta[$pasta] = $caminhos;
            $conjunto = array_merge($conjunto, $caminhos);
        }

        if ($conjunto === []) {
            return;
        }

        foreach (self::PLANO as $categoria => [$pasta, , , ]) {
            // sem galeria propria (download ainda em andamento) usa o conjunto
            // geral: melhor uma foto de outra categoria do que 400 produtos
            // repetindo a mesma imagem de espera
            $galeria = $porPasta[$pasta] ?? [];

            if ($galeria === []) {
                $galeria = $conjunto;
            }

            $produtos = DB::table('products')
                ->where('categoria', $categoria)
                ->where('imagem', 'like', self::PASTA_IMAGENS.'/%')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            foreach ($produtos as $i => $produtoId) {
                DB::table('products')->where('id', $produtoId)->update([
                    'imagem' => $galeria[$i % count($galeria)],
                ]);
            }
        }
    }

    private function criarProdutos(): void
    {
        $nomesUsados = DB::table('products')->pluck('nome')->flip();
        $lojaIds = DB::table('lojas')->orderBy('id')->pluck('id')->all();
        $imagens = $this->imagensPorCategoria();
        $agora = now();

        $novos = [];
        $total = DB::table('products')->count();

        foreach (self::PLANO as $categoria => [$pasta, $precoMin, $precoMax, $quantidade]) {
            $modelos = self::MODELOS[$categoria];
            $galeria = $imagens[$pasta] ?? [];
            $criadosNaCategoria = 0;
            $tentativa = 0;

            while ($criadosNaCategoria < $quantidade && $total < self::ALVO && $tentativa < $quantidade * 12) {
                $nome = $this->montarNome($categoria, $modelos, $tentativa);
                $tentativa++;

                if ($nomesUsados->has($nome)) {
                    continue;
                }

                $nomesUsados[$nome] = true;

                $preco = $this->preco($precoMin, $precoMax, $tentativa);
                // 1 em cada 6 entra em promocao
                $promocional = $tentativa % 6 === 0 ? round($preco * 0.78, 2) : null;

                $novos[] = [
                    'nome' => $nome,
                    'categoria' => $categoria,
                    'preco' => $preco,
                    'preco_promocional' => $promocional,
                    'imagem' => $this->imagemPara($galeria, $pasta, $criadosNaCategoria),
                    'descricao' => $this->descricao($categoria, $nome),
                    'loja_id' => $lojaIds === [] ? null : $lojaIds[$total % count($lojaIds)],
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];

                $criadosNaCategoria++;
                $total++;
            }
        }

        foreach (array_chunk($novos, 100) as $lote) {
            DB::table('products')->insert($lote);
        }
    }

    /**
     * Le as imagens disponiveis por pasta de categoria.
     *
     * @return array<string, array<int, string>>
     */
    private function imagensPorCategoria(): array
    {
        $raiz = public_path(self::PASTA_IMAGENS);
        $galerias = [];

        if (! is_dir($raiz)) {
            return $galerias;
        }

        foreach (scandir($raiz) ?: [] as $pasta) {
            if ($pasta === '.' || $pasta === '..') {
                continue;
            }

            $caminho = $raiz.DIRECTORY_SEPARATOR.$pasta;

            if (! is_dir($caminho)) {
                continue;
            }

            $arquivos = array_values(array_filter(
                scandir($caminho) ?: [],
                fn ($a) => str_ends_with(strtolower($a), '.jpg')
            ));

            sort($arquivos);
            $galerias[$pasta] = $arquivos;
        }

        return $galerias;
    }

    /**
     * @param  array<int, string>  $galeria
     */
    private function imagemPara(array $galeria, string $pasta, int $indice): string
    {
        if ($galeria !== []) {
            return self::PASTA_IMAGENS.'/'.$pasta.'/'.$galeria[$indice % count($galeria)];
        }

        // galeria ainda vazia: aponta para a imagem de espera, que fica sob
        // assets/catalogo/ para atribuirImagens() conseguir corrigir depois
        return self::IMAGEM_PENDENTE;
    }

    /**
     * @param  array<int, string>  $modelos
     */
    private function montarNome(string $categoria, array $modelos, int $i): string
    {
        $base = $categoria === 'Calçados' || $categoria === 'Acessórios'
            ? $modelos[$i % count($modelos)]
            : rtrim($this->singular($categoria)).' '.$modelos[$i % count($modelos)];

        $acabamento = self::ACABAMENTOS[intdiv($i, count($modelos)) % count(self::ACABAMENTOS)];
        $tom = self::TONS[intdiv($i, count($modelos) * 3) % count(self::TONS)];

        return trim("{$base} {$acabamento} {$tom}");
    }

    private function singular(string $categoria): string
    {
        return match ($categoria) {
            'Vestidos' => 'Vestido',
            'Blusas' => 'Blusa',
            'Camisas' => 'Camisa',
            'Saias' => 'Saia',
            'Calças' => 'Calça',
            'Casacos' => 'Casaco',
            default => rtrim($categoria, 's'),
        };
    }

    private function preco(int $min, int $max, int $i): float
    {
        $faixa = $max - $min;
        $valor = $min + (($i * 37) % max(1, $faixa));

        return round($valor - 0.10, 2);
    }

    private function descricao(string $categoria, string $nome): string
    {
        return match ($categoria) {
            'Calçados' => "{$nome}. Numeração 34 ao 40, solado antiderrapante.",
            'Acessórios' => "{$nome}. Peça de acabamento artesanal, tamanho único.",
            default => "{$nome}. Modelagem confortável, disponível do PP ao GG.",
        };
    }

    /** Cria variantes com saldo para todo produto que ainda nao tem. */
    private function criarEstoque(): void
    {
        $comVariante = DB::table('product_variants')->distinct()->pluck('product_id')->flip();

        $pendentes = DB::table('products')
            ->select('id', 'categoria')
            ->orderBy('id')
            ->get()
            ->reject(fn ($p) => $comVariante->has($p->id));

        $linhas = [];
        $agora = now();

        foreach ($pendentes as $produto) {
            $tamanhos = match ($produto->categoria) {
                'Calçados' => self::GRADE_CALCADO,
                'Acessórios' => ['Único'],
                default => self::GRADE_ROUPA,
            };

            $cores = $this->coresPara($produto->id);
            $indice = 0;

            foreach ($cores as [$cor, $hex]) {
                foreach ($tamanhos as $tamanho) {
                    $linhas[] = [
                        'product_id' => $produto->id,
                        'tamanho' => $tamanho,
                        'cor' => $cor,
                        'cor_hex' => $hex,
                        'estoque' => $this->saldo($produto->id, $tamanho, $indice),
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ];

                    $indice++;
                }
            }
        }

        foreach (array_chunk($linhas, 400) as $lote) {
            DB::table('product_variants')->insert($lote);
        }
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function coresPara(int $produtoId): array
    {
        $paleta = [
            ['Preto', '#1c1c1e'], ['Off-White', '#efe9df'], ['Bege', '#c8b7a2'],
            ['Camel', '#a97e50'], ['Vinho', '#5c1f2b'], ['Marinho', '#26324a'],
            ['Cinza', '#6f6f75'], ['Verde Musgo', '#4a5340'],
        ];

        $a = $paleta[$produtoId % count($paleta)];
        $b = $paleta[($produtoId * 3 + 1) % count($paleta)];

        return $a[0] === $b[0] ? [$a] : [$a, $b];
    }

    private function saldo(int $produtoId, string $tamanho, int $indice): int
    {
        $peso = match ($tamanho) {
            'PP', 'GG', '34', '40' => 2,
            'P', 'G', '35', '39' => 6,
            'Único' => 14,
            default => 11,
        };

        if (($produtoId * 3 + $indice) % 9 === 0) {
            return 0; // esgotado
        }

        return $peso + (($produtoId * 7 + $indice * 3) % 6);
    }
}
