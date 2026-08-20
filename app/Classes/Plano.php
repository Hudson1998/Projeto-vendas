<?php

namespace App\Classes;

class Plano
{
    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public float $valorMensal = 0,
        public int $limiteProduto = 0,
        public bool $destaqueBusca = false,
        public bool $suportePrioritario = false,
        public float $comissaoTaxa = 0,
        public float $divulgacaoValor = 0,
        public int $prazoRepasse = 0,
        public ?string $selo = null,
        public ?string $descricao = null,
    ) {
    }
}
