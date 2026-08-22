<?php

namespace App\Classes;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loja extends Model
{
    protected $fillable = [
        'user_id',

        'tipo_pessoa',
        'cpf',
        'cnpj',
        'razao_social',
        'nome_fantasia',
        'nome_exibicao',
        'inscricao_estadual',
        'ie_isento',
        'cnae',
        'regime_tributario',

        'nome_responsavel',
        'email',
        'whatsapp',

        'fiscal_cep',
        'fiscal_rua',
        'fiscal_numero',
        'fiscal_complemento',
        'fiscal_bairro',
        'fiscal_cidade',
        'fiscal_estado',

        'envio_cep',
        'envio_rua',
        'envio_numero',
        'envio_complemento',
        'envio_bairro',
        'envio_cidade',
        'envio_estado',

        'banco',
        'agencia',
        'conta',
        'titular_conta',
        'chave_pix',

        'logotipo',
        'bio_loja',

        'prazo_expedicao_dias_uteis',
        'politica_troca_devolucao',

        'documento_identidade_path',
        'selfie_documento_path',
        'contrato_social_mei_path',
        'comprovante_endereco_path',
        'comprovante_cnpj_path',

        'nivel_acesso',
        'plano_id',
    ];

    protected function casts(): array
    {
        return [
            'ie_isento' => 'boolean',
        ];
    }

    /**
     * Nome que a vitrine mostra ao comprador.
     *
     * nome_exibicao e opcional no cadastro; quando a loja nao escolhe um,
     * cai no nome_fantasia, que a migration exige.
     */
    public function nomeExibicao(): string
    {
        return $this->nome_exibicao ?: $this->nome_fantasia;
    }

    /**
     * Iniciais para o monograma que substitui o logotipo ausente.
     *
     * O campo logotipo e nullable e hoje nenhuma loja preencheu, entao esse
     * fallback e o que aparece na pratica -- nao e um caso de excecao raro.
     */
    public function iniciais(): string
    {
        $palavras = preg_split('/\s+/', trim($this->nomeExibicao()), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $iniciais = array_map(
            fn (string $palavra) => mb_strtoupper(mb_substr($palavra, 0, 1)),
            array_slice($palavras, 0, 2)
        );

        return implode('', $iniciais) ?: '?';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function transportadoras(): BelongsToMany
    {
        return $this->belongsToMany(Transportadora::class, 'loja_transportadora');
    }
}
