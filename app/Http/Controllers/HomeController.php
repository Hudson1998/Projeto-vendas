<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $produtos = Product::orderBy('categoria')->orderBy('nome')->get();

        return view('home', [
            'produtos' => $produtos,
        ]);
    }
}
