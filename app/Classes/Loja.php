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
