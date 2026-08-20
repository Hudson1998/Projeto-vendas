<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Amplia o catalogo de teste e cria o estoque real em product_variants.
 *
 * As classes de App\Classes (Camisa, Blusa, Acessorio...) sao apenas marcadores
 * que estendem Produto e nao guardam estoque; quem controla saldo e a tabela
 * product_variants, com uma linha por combinacao de tamanho e cor.
 *
 * Rode com `php artisan db:seed --class=CatalogoTesteSeeder`.
 */
class CatalogoTesteSeeder extends Seeder
{
    /** Grades de tamanho por tipo de peca. */
    private const GRADE_ROUPA = ['PP', 'P', 'M', 'G', 'GG'];

    private const GRADE_CALCADO = ['34', '35', '36', '37', '38', '39', '40'];

    private const CATEGORIAS_CALCADO = ['Calçados'];

    private const CATEGORIAS_ACESSORIO = ['Acessórios'];

    public function run(): void
    {
        $this->criarProdutos();
        $this->criarEstoque();
    }

    /**
     * Produtos que faltavam para cobrir a hierarquia de App\Classes --
     * nao havia nenhuma Blusa, nenhum Casaco e nenhum Calcado no catalogo.
     */
    private function criarProdutos(): void
    {
        $novos = [
            ['Blusa de Tricô Canelada', 'Blusas', 129.90, null, 'blusa-trico.svg', 'Tricô canelado de toque macio, modelagem justa ao corpo.'],
            ['Blusa Cropped Básica', 'Blusas', 79.90, 59.90, 'blusa-cropped.svg', 'Malha de algodão com caimento leve e barra cropped.'],
            ['Blusa de Linho Manga Bufante', 'Blusas', 139.90, null, 'blusa-linho.svg', 'Linho leve com manga bufante e punho ajustado.'],

            ['Casaco Trench Bege', 'Casacos', 349.90, 279.90, 'casaco-trench.svg', 'Trench clássico com cinto e forro interno.'],
            ['Blazer Oversized', 'Casacos', 289.90, null, 'blazer-oversized.svg', 'Alfaiataria de caimento amplo, ombro estruturado.'],

            ['Scarpin Salto Fino', 'Calçados', 219.90, null, 'scarpin-salto.svg', 'Bico fino e salto de 8 cm forrado.'],
            ['Tênis Branco Minimalista', 'Calçados', 199.90, 169.90, 'tenis-branco.svg', 'Couro liso, solado de borracha e cabedal sem estampas.'],
            ['Bota Cano Curto', 'Calçados', 299.90, null, 'bota-cano-curto.svg', 'Cano curto com zíper lateral e salto bloco.'],
            ['Sandália Tira Fina', 'Calçados', 159.90, null, 'sandalia-tira-fina.svg', 'Tiras finas com fivela ajustável no tornozelo.'],

            ['Óculos de Sol Redondo', 'Acessórios', 149.90, null, 'oculos-sol.svg', 'Armação metálica redonda com lente polarizada.'],
            ['Relógio Dourado Slim', 'Acessórios', 279.90, null, 'relogio-dourado.svg', 'Caixa slim de 32 mm com pulseira de malha.'],
            ['Brinco Argola Pequena', 'Acessórios', 69.90, null, 'brinco-argola.svg', 'Argola de 2 cm banhada, fecho de pressão.'],
            ['Colar Corrente Fina', 'Acessórios', 89.90, null, 'colar-corrente.svg', 'Corrente veneziana de 45 cm com extensor.'],
        ];

        foreach ($novos as [$nome, $categoria, $preco, $promocional, $imagem, $descricao]) {
            Product::updateOrCreate(
                ['nome' => $nome],
                [
                    'categoria' => $categoria,
                    'preco' => $preco,
                    'preco_promocional' => $promocional,
                    'imagem' => 'assets/'.$imagem,
                    'descricao' => $descricao,
                ]
            );
        }
    }

    /**
     * Uma linha de estoque por combinacao tamanho x cor.
     *
     * Os saldos sao deterministicos (derivados do id do produto) para que
     * reexecutar o seeder nao embaralhe os numeros, e alguns combos ficam
     * zerados de proposito para exercitar o caso "esgotado".
     */
    private function criarEstoque(): void
    {
        foreach (Product::all() as $produto) {
            $tamanhos = $this->gradePara($produto->categoria);
            $cores = $this->coresPara($produto);

            $indice = 0;

            foreach ($cores as [$cor, $hex]) {
                foreach ($tamanhos as $tamanho) {
                    ProductVariant::updateOrCreate(
                        [
                            'product_id' => $produto->id,
                            'tamanho' => $tamanho,
                            'cor' => $cor,
                        ],
                        [
                            'cor_hex' => $hex,
                            'estoque' => $this->saldo($produto->id, $tamanho, $indice),
                        ]
                    );

                    $indice++;
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function gradePara(string $categoria): array
    {
        if (in_array($categoria, self::CATEGORIAS_CALCADO, true)) {
            return self::GRADE_CALCADO;
        }

        if (in_array($categoria, self::CATEGORIAS_ACESSORIO, true)) {
            return ['Único'];
        }

        return self::GRADE_ROUPA;
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function coresPara(Product $produto): array
    {
        $paletas = [
            'Vestidos' => [['Preto', '#1c1c1e'], ['Vinho', '#5c1f2b']],
            'Saias' => [['Cinza', '#6f6f75'], ['Bege', '#c8b7a2']],
            'Camisas' => [['Off-White', '#efe9df'], ['Preto', '#1c1c1e']],
            'Blusas' => [['Off-White', '#efe9df'], ['Camel', '#a97e50']],
            'Casacos' => [['Bege', '#c8b7a2'], ['Preto', '#1c1c1e']],
            'Calças' => [['Preto', '#1c1c1e'], ['Marinho', '#26324a']],
            'Calçados' => [['Preto', '#1c1c1e'], ['Branco', '#f2f0ec']],
            'Acessórios' => [['Dourado', '#b08d55'], ['Prata', '#c9c9ce']],
        ];

        return $paletas[$produto->categoria] ?? [['Único', '#8a8a90']];
    }

    /**
     * Saldo estavel por produto/tamanho, com tamanhos centrais mais abastecidos
     * e alguns zerados para representar peca esgotada.
     */
    private function saldo(int $produtoId, string $tamanho, int $indice): int
    {
        // tamanhos das pontas vendem menos e chegam em menor quantidade
        $peso = match ($tamanho) {
            'PP', 'GG', '34', '40' => 2,
            'P', 'G', '35', '39' => 6,
            'Único' => 14,
            default => 11,
        };

        $variacao = ($produtoId * 7 + $indice * 3) % 5;
        $saldo = $peso + $variacao;

        // ~1 em cada 9 combinacoes fica esgotada
        if (($produtoId * 3 + $indice) % 9 === 0) {
            return 0;
        }

        return $saldo;
    }
}
