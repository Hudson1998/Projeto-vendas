<?php

namespace App\Http\Controllers;

use App\Pages\PaginaInicial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $paginaInicial = new PaginaInicial;

        return view('home', [
            'produtos' => $paginaInicial->filtro(),
            'carrosselMaisComprados' => $paginaInicial->carrosselMaisComprados(10),
            'carrosselMaisVisitados' => $paginaInicial->carrosselMaisVisitados(10),
            'carrosselPromocoes' => $paginaInicial->carrosselPromocoes(10),
        ]);
    }
}
