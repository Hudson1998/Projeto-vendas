<?php

namespace App\Models;

use App\Classes\Motorista;
use App\Classes\Transportadora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'distancia_km',
        'valor_frete',
        'status',
        'avaliacao',
        'forma_pagamento',
        'tipo_entrega',
        'endereco_entrega',
        'status_pagamento',
        'comprovante_pagamento_path',
        'ip_compra',
        'localizacao',
        'codigo_pagamento',
        'verificado_banco',
        'status_separacao',
        'entrega_propria',
        'transportadora_id',
        'motorista_id',
        'fragil',
        'dimensoes',
        'analisado_por',
        'analisado_em',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'distancia_km' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'fragil' => 'boolean',
        'entrega_propria' => 'boolean',
        'verificado_banco' => 'boolean',
        'analisado_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function analisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }

    public function transportadora(): BelongsTo
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }
}
