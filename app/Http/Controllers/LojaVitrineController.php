<?php

namespace App\Http\Controllers;

use App\Classes\Loja;
use Illuminate\View\View;

/**
 * Vitrine publica de uma loja.
 *
 * Nao confundir com o LojaDashboardController, que fica atras de auth+lojista
 * e serve o painel de quem vende. Aqui e a pagina que o comprador ve ao clicar
 * na loja a partir de um produto, sem precisar de login.
 */
class LojaVitrineController extends Controller
{
    public function show(Loja $loja): View
    {
        $produtos = $loja->produtos()
            ->with('subclass')
            ->orderBy('nome')
            ->get();

        return view('lojas.show', [
            'loja' => $loja,
            'produtos' => $produtos,
        ]);
    }
}
