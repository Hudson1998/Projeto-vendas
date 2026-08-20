<?php

namespace App\Classes;

class Filtro
{
    public function __construct(
        public string $nome = '',
        public ?string $produto = null,
        public ?string $loja = null,
        public ?float $valor = null,
    ) {
    }
}
