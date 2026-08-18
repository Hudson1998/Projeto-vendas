<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LojistaAuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->middleware('log.visit')->name('home');

Route::post('/buscas', [SearchLogController::class, 'store'])->name('buscas.store');

Route::get('/produtos/{product}', [ProductController::class, 'show'])->name('products.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register']);
    Route::get('/cadastro/lojista', [LojistaAuthController::class, 'showRegister'])->name('register.lojista');
    Route::post('/cadastro/lojista', [LojistaAuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
    Route::post('/carrinho', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/carrinho/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/carrinho/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::delete('/carrinho', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/carrinho/finalizar', [CartController::class, 'checkout'])->name('cart.checkout');

    Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/pedidos/{order}/cancelar', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/pedidos/{order}/avaliar', [OrderController::class, 'rate'])->name('orders.rate');

    Route::get('/favoritos', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favoritos/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::post('/produtos/{product}/avaliacoes', [ProductReviewController::class, 'store'])->name('products.reviews.store');

    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/perfil', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/stats', [AdminController::class, 'stats'])->name('stats');
    Route::get('/graficos', [AdminController::class, 'charts'])->name('charts');
    Route::get('/graficos/acessos', [AdminController::class, 'acessos'])->name('charts.acessos');
    Route::get('/graficos/receita', [AdminController::class, 'receita'])->name('charts.receita');
    Route::get('/graficos/volume-compras', [AdminController::class, 'volumeCompras'])->name('charts.volume');
    Route::get('/graficos/satisfacao', [AdminController::class, 'satisfacao'])->name('charts.satisfacao');
    Route::get('/graficos/vendas-categoria', [AdminController::class, 'vendasPorCategoria'])->name('charts.categorias');
    Route::get('/clientes', [AdminController::class, 'clientes'])->name('clientes');
    Route::get('/emails', [AdminController::class, 'emails'])->name('emails');
    Route::get('/faturamento', [AdminController::class, 'faturamento'])->name('faturamento');
    Route::get('/faturamento/dados', [AdminController::class, 'faturamentoDados'])->name('faturamento.dados');
    Route::post('/faturamento/configuracoes', [AdminController::class, 'atualizarConfiguracoes'])->name('faturamento.config');
    Route::get('/produtos', [AdminController::class, 'produtos'])->name('produtos');
    Route::get('/produtos/novo', [ProductController::class, 'create'])->name('products.create');
    Route::post('/produtos', [ProductController::class, 'store'])->name('products.store');
    Route::get('/produtos/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/produtos/{product}', [ProductController::class, 'update'])->name('products.update');
});
