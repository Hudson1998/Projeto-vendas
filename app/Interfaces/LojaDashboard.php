<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface LojaDashboard
{
    public function visitantes(int $lojaId): Collection;

    public function vendasPorDia(int $lojaId, int $dias = 14): BaseCollection;

    public function visitasPorDia(int $lojaId, int $dias = 14): BaseCollection;

    public function produtosMaisVendidos(int $lojaId, int $limite = 10): Collection;

    public function produtosMaisVisitados(int $lojaId, int $limite = 10): Collection;
}
