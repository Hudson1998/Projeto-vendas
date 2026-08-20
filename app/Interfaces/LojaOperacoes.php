<?php

namespace App\Interfaces;

use App\Classes\Loja;

interface LojaOperacoes
{
    public function cadastrar(array $dados): Loja;

    public function viabilizarTransferencia(Loja $loja): bool;

    public function gerarDocumento(Loja $loja): string;
}
