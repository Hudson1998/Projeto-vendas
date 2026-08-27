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

    /** @return array<int, array{label: string, valor: float}> */
    public function lucroPorPeriodo(int $lojaId, string $granularidade = 'dia'): array;

    /** @return array<int, array{label: string, valor: float}> */
    public function visitantesPorPeriodo(int $lojaId, string $granularidade = 'dia'): array;

    /** @return array<int, array{id: int, nome: string, visitas: int}> */
    public function visitasPorProduto(int $lojaId, int $limite = 12): array;

    public function tabelaDeVendas(int $lojaId, int $limite = 40): BaseCollection;
}
