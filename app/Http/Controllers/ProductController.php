<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
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

        $foto = $request->file('foto');
        $nomeArquivo = uniqid('produto_').'.'.$foto->getClientOriginalExtension();
        $foto->move(public_path('uploads'), $nomeArquivo);

        Product::create([
            'nome' => $data['nome'],
            'categoria' => $data['categoria'],
            'preco' => $data['preco'],
            'custo' => $data['custo'] ?? 0,
            'imagem' => 'uploads/'.$nomeArquivo,
            'descricao' => $data['descricao'] ?? null,
            'estoque' => $data['estoque'],
            'tamanhos' => $this->parseTamanhos($request),
            'cores' => $this->parseCores($request),
        ]);

        return redirect()->route('admin.products.create')->with('status', 'Peça cadastrada com sucesso!');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, false);

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
            'custo' => $data['custo'] ?? 0,
            'imagem' => $imagem,
            'descricao' => $data['descricao'] ?? null,
            'estoque' => $data['estoque'],
            'tamanhos' => $this->parseTamanhos($request),
            'cores' => $this->parseCores($request),
        ]);

        return redirect()->route('admin.produtos')->with('status', 'Peça "'.$product->nome.'" atualizada com sucesso!');
    }

    public function updateCusto(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'custo' => ['required', 'numeric', 'min:0'],
        ]);

        $product->update(['custo' => $data['custo']]);

        return back()->with('status', 'Custo de "'.$product->nome.'" atualizado.');
    }

    private function validateProduct(Request $request, bool $fotoRequired): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'custo' => ['nullable', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'estoque' => ['required', 'integer', 'min:0'],
            'foto' => [$fotoRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tamanhos' => ['nullable', 'string', 'max:255'],
            'cores_nome' => ['nullable', 'array'],
            'cores_nome.*' => ['nullable', 'string', 'max:60'],
            'cores_hex' => ['nullable', 'array'],
            'cores_hex.*' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function parseTamanhos(Request $request): ?array
    {
        $bruto = (string) $request->input('tamanhos', '');
        $tamanhos = array_values(array_filter(array_map('trim', explode(',', $bruto))));

        return $tamanhos ?: null;
    }

    private function parseCores(Request $request): ?array
    {
        $nomes = $request->input('cores_nome', []);
        $hexes = $request->input('cores_hex', []);

        $cores = [];
        foreach ($nomes as $i => $nome) {
            $nome = trim((string) $nome);
            if ($nome === '') {
                continue;
            }
            $cores[] = [
                'nome' => $nome,
                'hex' => $hexes[$i] ?? '#000000',
            ];
        }

        return $cores ?: null;
    }
}
