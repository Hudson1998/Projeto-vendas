<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Pages\PaginaInicial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        $product = (new PaginaInicial)->linkProduto($product->id);

        $reviews = $product->reviews()->with('user')->latest()->get();

        $user = auth()->user();

        return view('products.show', [
            'product' => $product,
            'reviews' => $reviews,
            'mediaAvaliacao' => $reviews->count() ? round($reviews->avg('avaliacao'), 1) : null,
            'jaComprou' => $user ? $product->purchasedBy($user->id) : false,
            'minhaAvaliacao' => $user ? $reviews->firstWhere('user_id', $user->id) : null,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request, true);
        $variantes = $this->parseVariantes($request);

        $foto = $request->file('foto');
        $nomeArquivo = uniqid('produto_').'.'.$foto->getClientOriginalExtension();
        $foto->move(public_path('uploads'), $nomeArquivo);

        $product = Product::create([
            'nome' => $data['nome'],
            'categoria' => $data['categoria'],
            'preco' => $data['preco'],
            'imagem' => 'uploads/'.$nomeArquivo,
            'descricao' => $data['descricao'] ?? null,
        ]);

        foreach ($variantes as $variante) {
            $product->variants()->create($variante);
        }

        return redirect()->route('admin.products.create')->with('status', 'Peça cadastrada com sucesso!');
    }

    public function edit(Product $product): View
    {
        $product->load('variants');

        return view('admin.products.edit', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, false);
        $variantes = $this->parseVariantes($request);

        $imagem = $product->imagem;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $nomeArquivo = uniqid('produto_').'.'.$foto->getClientOriginalExtension();
            $foto->move(public_path('uploads'), $nomeArquivo);
            $imagem = 'uploads/'.$nomeArquivo;
        }

        $product->update([
            'nome' => $data['nome'],
            'categoria' => $data['categoria'],
            'preco' => $data['preco'],
            'imagem' => $imagem,
            'descricao' => $data['descricao'] ?? null,
        ]);

        $product->variants()->delete();
        foreach ($variantes as $variante) {
            $product->variants()->create($variante);
        }

        return redirect()->route('admin.produtos')->with('status', 'Peça "'.$product->nome.'" atualizada com sucesso!');
    }

    private function validateProduct(Request $request, bool $fotoRequired): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'foto' => [$fotoRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'variantes_tamanho' => ['nullable', 'array'],
            'variantes_tamanho.*' => ['nullable', 'string', 'max:60'],
            'variantes_cor' => ['nullable', 'array'],
            'variantes_cor.*' => ['nullable', 'string', 'max:60'],
            'variantes_cor_hex' => ['nullable', 'array'],
            'variantes_cor_hex.*' => ['nullable', 'string', 'max:20'],
            'variantes_estoque' => ['nullable', 'array'],
            'variantes_estoque.*' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * @return array<int, array{tamanho: ?string, cor: ?string, cor_hex: ?string, estoque: int}>
     */
    private function parseVariantes(Request $request): array
    {
        $tamanhos = $request->input('variantes_tamanho', []);
        $cores = $request->input('variantes_cor', []);
        $hexes = $request->input('variantes_cor_hex', []);
        $estoques = $request->input('variantes_estoque', []);

        $linhas = max(count($tamanhos), count($cores), count($estoques));
        $variantes = [];

        for ($i = 0; $i < $linhas; $i++) {
            $tamanho = trim((string) ($tamanhos[$i] ?? '')) ?: null;
            $cor = trim((string) ($cores[$i] ?? '')) ?: null;
            $hex = trim((string) ($hexes[$i] ?? '')) ?: null;
            $estoqueBruto = $estoques[$i] ?? null;

            if ($tamanho === null && $cor === null && ($estoqueBruto === null || $estoqueBruto === '')) {
                continue;
            }

            $variantes[] = [
                'tamanho' => $tamanho,
                'cor' => $cor,
                'cor_hex' => $cor !== null ? ($hex ?: '#000000') : null,
                'estoque' => (int) ($estoqueBruto ?? 0),
            ];
        }

        if (empty($variantes)) {
            throw ValidationException::withMessages([
                'variantes_estoque' => 'Cadastre ao menos uma variante com estoque.',
            ]);
        }

        return $variantes;
    }
}
