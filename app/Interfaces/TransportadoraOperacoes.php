<?php

namespace App\Interfaces;

use App\Classes\Motorista;
use App\Classes\Transportadora;
use Illuminate\Database\Eloquent\Collection;

interface TransportadoraOperacoes
{
    public function cadastrarTransportadora(array $dados): Transportadora;

    public function cadastrarMotorista(array $dados): Motorista;

    public function listarDisponiveis(?int $lojaId = null): Collection;

    public function vincularLoja(int $lojaId, int $transportadoraId): void;

    public function desvincularLoja(int $lojaId, int $transportadoraId): void;
}
