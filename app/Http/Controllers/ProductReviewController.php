<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        abort_unless($product->purchasedBy($user->id), 403, 'Você precisa ter comprado esta peça para avaliá-la.');

        $data = $request->validate([
            'avaliacao' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        ProductReview::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $user->id],
            ['avaliacao' => $data['avaliacao'], 'comentario' => $data['comentario'] ?? null]
        );

        return back()->with('status', 'Avaliação salva!');
    }
}
