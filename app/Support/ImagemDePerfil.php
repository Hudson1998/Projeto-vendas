<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Guarda a imagem de um perfil (foto do cliente, logotipo da loja).
 *
 * Segue o mesmo formato que o ProductController ja usa para as fotos de
 * produto: o arquivo vai para public/uploads e o banco guarda o caminho
 * relativo "uploads/nome.ext", que o asset() resolve direto na view.
 */
class ImagemDePerfil
{
    /**
     * Regras de validacao, iguais as da foto de produto para que o usuario
     * nao encontre limites diferentes dependendo da tela.
     *
     * @return array<int, string>
     */
    public static function regras(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
    }

    /**
     * Move o arquivo enviado e devolve o caminho a gravar no banco.
     *
     * Quando substitui uma imagem anterior, apaga a antiga: sem isso cada
     * troca deixaria um orfao em public/uploads para sempre.
     */
    public static function guardar(UploadedFile $arquivo, string $prefixo, ?string $anterior = null): string
    {
        $nomeArquivo = uniqid($prefixo).'.'.$arquivo->getClientOriginalExtension();
        $arquivo->move(public_path('uploads'), $nomeArquivo);

        self::apagar($anterior);

        return 'uploads/'.$nomeArquivo;
    }

    /**
     * Remove uma imagem guardada por esta classe.
     *
     * So apaga dentro de public/uploads: o basename descarta qualquer "../"
     * e o prefixo fixo impede que um caminho vindo do banco alcance outra
     * pasta caso um dia seja gravado por outro caminho.
     */
    public static function apagar(?string $caminho): void
    {
        if (! $caminho || ! str_starts_with($caminho, 'uploads/')) {
            return;
        }

        $arquivo = public_path('uploads/'.basename($caminho));

        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }
}
