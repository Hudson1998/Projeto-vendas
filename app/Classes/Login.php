<?php

namespace App\Classes;

class Login
{
    public function __construct(
        public ?int $id = null,
        public string $email = '',
        public string $senha = '',
        public string $tipoDeUsuario = '',
        public ?string $ultimoAcesso = null,
        public int $tentativasFalhas = 0,
    ) {
    }
}
