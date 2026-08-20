<?php

namespace App\Classes;

class Produto
{
    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public ?int $loja = null,
        public float $valor = 0,
        public string $tipo = '',
        public bool $fragil = false,
    ) {
    }
}
