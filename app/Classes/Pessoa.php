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
        public ?string $foto = null,
    ) {
    }

    /**
     * Iniciais para o monograma que substitui a foto ausente.
     */
    public function iniciais(): string
    {
        $palavras = preg_split('/\s+/', trim($this->nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $iniciais = array_map(
            fn (string $palavra) => mb_strtoupper(mb_substr($palavra, 0, 1)),
            array_slice($palavras, 0, 2)
        );

        return implode('', $iniciais) ?: '?';
    }
}
