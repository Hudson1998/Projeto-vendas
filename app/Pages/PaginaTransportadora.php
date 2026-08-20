<?php

namespace App\Pages;

use App\Classes\Loja;
use App\Classes\Motorista;
use App\Classes\Transportadora;
use App\Interfaces\TransportadoraOperacoes;
use Illuminate\Database\Eloquent\Collection;

class PaginaTransportadora implements TransportadoraOperacoes
{
    public function cadastrarTransportadora(array $dados): Transportadora
    {
        return Transportadora::create($dados);
    }

    public function cadastrarMotorista(array $dados): Motorista
    {
        return Motorista::create($dados);
    }

    public function listarDisponiveis(?int $lojaId = null): Collection
    {
        if ($lojaId !== null) {
            $loja = Loja::find($lojaId);

            return $loja ? $loja->transportadoras()->where('ativo', true)->get() : new Collection;
        }

        return Transportadora::where('ativo', true)->orderBy('nome')->get();
    }

    public function vincularLoja(int $lojaId, int $transportadoraId): void
    {
        $loja = Loja::findOrFail($lojaId);
        $loja->transportadoras()->syncWithoutDetaching([$transportadoraId]);
    }

    public function desvincularLoja(int $lojaId, int $transportadoraId): void
    {
        $loja = Loja::findOrFail($lojaId);
        $loja->transportadoras()->detach($transportadoraId);
    }
}
