<?php

namespace App\Classes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transportadora extends Model
{
    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'email',
        'tipo_veiculo',
        'area_atuacao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function motoristas(): HasMany
    {
        return $this->hasMany(Motorista::class);
    }

    public function lojas(): BelongsToMany
    {
        return $this->belongsToMany(Loja::class, 'loja_transportadora');
    }
}
