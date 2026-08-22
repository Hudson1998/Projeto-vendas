<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LojaDashboardController;
use App\Http\Controllers\LojaVitrineController;
use App\Http\Controllers\LojistaAuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->middleware('log.visit')->name('home');

Route::post('/buscas', [SearchLogController::class, 'store'])->name('buscas.store');

Route::get('/produtos/{product}', [ProductController::class, 'show'])->middleware('log.visit')->name('products.show');

// vitrine publica da loja, alcancada pelo bloco da loja na pagina do produto.
// O painel do lojista mora em /loja (singular), atras de auth+lojista.
Route::get('/lojas/{loja}', [LojaVitrineController::class, 'show'])->middleware('log.visit')->name('lojas.show');

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
    Route::get('/pedidos/acompanhar', [OrderController::class, 'tracking'])->name('orders.tracking');
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

Route::middleware(['auth', 'lojista'])->prefix('loja')->name('loja.')->group(function () {
    Route::get('/', [LojaDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/dados', [LojaDashboardController::class, 'dados'])->name('dados');

    Route::get('/pedidos', [LojaDashboardController::class, 'pedidos'])->name('pedidos');
    Route::post('/pedidos/{order}/entrega', [LojaDashboardController::class, 'definirEntrega'])->name('pedidos.entrega');
    Route::post('/pedidos/{order}/separar', [LojaDashboardController::class, 'separar'])->name('pedidos.separar');
    Route::post('/pedidos/{order}/embalar', [LojaDashboardController::class, 'embalar'])->name('pedidos.embalar');
    Route::post('/pedidos/{order}/enviar', [LojaDashboardController::class, 'enviar'])->name('pedidos.enviar');

    Route::get('/transportadoras', [LojaDashboardController::class, 'transportadoras'])->name('transportadoras');
    Route::post('/transportadoras', [LojaDashboardController::class, 'cadastrarTransportadora'])->name('transportadoras.store');
    Route::post('/transportadoras/{transportadora}/vincular', [LojaDashboardController::class, 'vincular'])->name('transportadoras.vincular');
    Route::post('/transportadoras/{transportadora}/desvincular', [LojaDashboardController::class, 'desvincular'])->name('transportadoras.desvincular');
    Route::post('/motoristas', [LojaDashboardController::class, 'cadastrarMotorista'])->name('motoristas.store');

    Route::get('/clientes', [LojaDashboardController::class, 'clientes'])->name('clientes');
    Route::get('/produtos', [LojaDashboardController::class, 'produtos'])->name('produtos');
});
