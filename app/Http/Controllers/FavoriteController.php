<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $produtos = Favorite::with('product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->pluck('product')
            ->filter();

        return view('favorites.index', ['produtos' => $produtos]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $favorite = Favorite::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json(['favoritado' => false]);
        }

        Favorite::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['favoritado' => true]);
    }
}
