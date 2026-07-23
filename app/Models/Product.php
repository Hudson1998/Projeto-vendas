<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'nome',
        'categoria',
        'preco',
        'imagem',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
    ];
}
