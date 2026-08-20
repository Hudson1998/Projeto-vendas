<?php

namespace App\Classes;

class Pessoa
{
    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public string $email = '',
        public ?string $cpf = null,
        public ?string $dataNascimento = null,
        public ?string $endereco = null,
    ) {
    }
}
