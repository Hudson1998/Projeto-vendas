<?php

namespace App\Models;

use App\Classes\Loja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saque extends Model
{
    protected $table = 'saques';

    protected $fillable = [
        'loja_id',
        'valor',
        'status',
        'destino',
        'processado_em',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'processado_em' => 'datetime',
    ];

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class);
    }
}
