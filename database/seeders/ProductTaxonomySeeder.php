<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductClass;
use App\Models\ProductSubclass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Classes e subclasses de produto espelham a hierarquia de app/Classes
 * (Produto > Roupa/Acessorio/Calcado > subtipos). Aqui elas viram tabelas
 * reais para classificar os produtos do catalogo, sem mexer no campo
 * `categoria` livre que o resto do site (filtro, busca, graficos) ja usa.
 */
class ProductTaxonomySeeder extends Seeder
{
    /** classe => subclasses */
    private const TAXONOMIA = [
        'Roupa' => ['Blusa', 'Calça', 'Camisa', 'Casaco', 'Íntimo', 'Meia', 'Saia', 'Vestido'],
        'Calçado' => ['Bota', 'Chinelo', 'Mocassim', 'Mule', 'Pantufa', 'Salto', 'Sandália', 'Tênis'],
        'Acessório' => ['Anel', 'Bolsa', 'Boné', 'Bracelete', 'Brinco', 'Chapéu', 'Cinto', 'Cordão', 'Lenço', 'Óculos', 'Perfume', 'Pulseira', 'Relógio'],
    ];

    /** categoria (products.categoria) => slug da subclasse, para produtos ja cadastrados */
    private const CATEGORIA_PARA_SUBCLASSE = [
        'Vestidos' => 'vestido',
        'Saias' => 'saia',
        'Camisas' => 'camisa',
        'Calças' => 'calca',
        'Blusas' => 'blusa',
        'Casacos' => 'casaco',
    ];

    /**
     * Nome comercial => subclasse, para peças que o catalogo anuncia por um
     * nome que nao e o da subclasse. Categorias "achatadas" (Calçados,
     * Acessórios) nao dizem o subtipo, entao a classificacao sai do nome do
     * produto — e "Scarpin"/"Ankle Boot"/"Colar" nunca casariam sozinhos com
     * "Salto"/"Bota"/"Cordão".
     */
    private const SINONIMOS = [
        'scarpin' => 'salto',
        'ankle boot' => 'bota',
        'rasteira' => 'sandalia',
        'colar' => 'cordao',
    ];

    public function run(): void
    {
        $subclassesPorSlug = [];

        foreach (self::TAXONOMIA as $classeNome => $subclasses) {
            $classe = ProductClass::firstOrCreate(
                ['slug' => Str::slug($classeNome)],
                ['nome' => $classeNome]
            );

            foreach ($subclasses as $subclasseNome) {
                $slug = Str::slug($subclasseNome);
                $subclassesPorSlug[$slug] = ProductSubclass::firstOrCreate(
                    ['slug' => $slug],
                    ['nome' => $subclasseNome, 'product_class_id' => $classe->id]
                );
            }
        }

        Product::whereNull('product_subclass_id')->get()->each(function (Product $produto) use ($subclassesPorSlug) {
            $slug = self::CATEGORIA_PARA_SUBCLASSE[$produto->categoria]
                ?? $this->porNomeDoProduto($produto->nome, $subclassesPorSlug);

            if ($slug !== null && isset($subclassesPorSlug[$slug])) {
                $produto->update(['product_subclass_id' => $subclassesPorSlug[$slug]->id]);
            }
        });
    }

    /**
     * Para categorias "achatadas" (ex.: "Calçados", "Acessórios", que nao
     * dizem o subtipo), tenta casar pelo nome da subclasse dentro do nome
     * do produto — ex.: "Bolsa Estruturada" -> bolsa, "Tênis Branco" -> tenis.
     * Antes disso consulta os sinonimos, que sao afirmacoes explicitas e por
     * isso valem mais que a coincidencia de substring.
     *
     * @param  array<string, ProductSubclass>  $subclassesPorSlug
     */
    private function porNomeDoProduto(string $nomeProduto, array $subclassesPorSlug): ?string
    {
        $nomeNormalizado = Str::slug($nomeProduto, ' ');

        foreach (self::SINONIMOS as $sinonimo => $slug) {
            if (Str::contains($nomeNormalizado, $sinonimo)) {
                return $slug;
            }
        }

        foreach ($subclassesPorSlug as $slug => $subclasse) {
            $subclasseNormalizada = Str::slug($subclasse->nome, ' ');
            if (Str::contains($nomeNormalizado, $subclasseNormalizada)) {
                return $slug;
            }
        }

        return null;
    }
}
