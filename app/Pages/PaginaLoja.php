<?php

namespace App\Pages;

use App\Classes\Loja;
use App\Interfaces\LojaOperacoes;
use Illuminate\Support\Facades\Storage;

class PaginaLoja implements LojaOperacoes
{
    public function cadastrar(array $dados): Loja
    {
        return Loja::create($dados);
    }

    public function viabilizarTransferencia(Loja $loja): bool
    {
        return filled($loja->chave_pix)
            || (filled($loja->banco) && filled($loja->agencia) && filled($loja->conta));
    }

    public function gerarDocumento(Loja $loja): string
    {
        $conteudo = implode("\n", [
            'Comprovante de Cadastro de Loja',
            '================================',
            'Nome fantasia: '.$loja->nome_fantasia,
            'Razão social: '.($loja->razao_social ?? '—'),
            'CNPJ: '.($loja->cnpj ?? '—'),
            'CPF: '.($loja->cpf ?? '—'),
            'Responsável: '.$loja->nome_responsavel,
            'WhatsApp: '.$loja->whatsapp,
            'Endereço fiscal: '.implode(', ', array_filter([
                $loja->fiscal_rua, $loja->fiscal_numero, $loja->fiscal_bairro,
                $loja->fiscal_cidade, $loja->fiscal_estado,
            ])),
            'Data de cadastro: '.$loja->created_at?->format('d/m/Y H:i'),
        ]);

        $caminho = "lojas/{$loja->id}/comprovante-cadastro.txt";

        Storage::disk('local')->put($caminho, $conteudo);

        return $caminho;
    }
}
