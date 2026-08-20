<?php

namespace App\Classes;

class ADM extends Pessoa
{
    public function __construct(
        ?int $id = null,
        string $nome = '',
        string $email = '',
        ?string $cpf = null,
        ?string $dataNascimento = null,
        ?string $endereco = null,
        public string $nivelAcesso = '',
        public string $senha = '',
        public string $cargo = '',
    ) {
        parent::__construct($id, $nome, $email, $cpf, $dataNascimento, $endereco);
    }
}
