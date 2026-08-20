<?php

namespace App\Interfaces;

use App\Classes\Login;
use App\Classes\Pessoa;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface PagInicial
{
    public function login(string $email, string $senha, bool $lembrar = false): Login;

    public function filtro(?string $categoria = null): Collection;

    public function busca(string $termo, ?int $userId = null): Collection;

    public function notificacao(int $userId): array;

    public function carrinho(int $userId): Collection;

    public function perfil(int $userId): ?Pessoa;

    public function linkProduto(int $productId): ?Product;

    public function carrosselMaisComprados(int $limite = 10): Collection;

    public function carrosselMaisAvaliados(int $limite = 10): Collection;

    public function carrosselMaisVisitados(int $limite = 10): Collection;

    public function carrosselPromocoes(int $limite = 10): Collection;

    public function carrosselPremium(int $limite = 10): Collection;
}
