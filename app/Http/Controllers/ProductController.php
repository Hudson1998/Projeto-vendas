<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function create(): View
    {
        return view('admin.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'custo' => ['nullable', 'numeric', 'min:0'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $foto = $request->file('foto');
        $nomeArquivo = uniqid('produto_').'.'.$foto->getClientOriginalExtension();
        $foto->move(public_path('uploads'), $nomeArquivo);

        Product::create([
            'nome' => $data['nome'],
            'categoria' => $data['categoria'],
            'preco' => $data['preco'],
            'custo' => $data['custo'] ?? 0,
            'imagem' => 'uploads/'.$nomeArquivo,
        ]);

        return redirect()->route('admin.products.create')->with('status', 'Peça cadastrada com sucesso!');
    }

    public function updateCusto(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'custo' => ['required', 'numeric', 'min:0'],
        ]);

        $product->update(['custo' => $data['custo']]);

        return back()->with('status', 'Custo de "'.$product->nome.'" atualizado.');
    }
}
