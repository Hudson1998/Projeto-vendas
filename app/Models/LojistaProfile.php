<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LojistaProfile extends Model
{
    protected $fillable = [
        'user_id',
        'telefone',
        'nome_fantasia',
        'tipo_pessoa',
        'cpf',
        'cnpj',
        'razao_social',
        'inscricao_estadual',
        'ie_isento',
        'logotipo',
        'descricao_loja',
        'cep',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'prazo_expedicao_dias_uteis',
        'politica_troca_devolucao',
        'doc_identidade_path',
        'selfie_documento_path',
        'contrato_social_mei_path',
        'comprovante_endereco_path',
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
}
