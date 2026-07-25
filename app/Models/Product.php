<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'nome',
        'categoria',
        'preco',
        'custo',
        'imagem',
        'descricao',
        'estoque',
        'tamanhos',
        'cores',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'custo' => 'decimal:2',
        'tamanhos' => 'array',
        'cores' => 'array',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function purchasedBy(int $userId): bool
    {
        return OrderItem::where('product_id', $this->id)
            ->whereHas('order', fn ($query) => $query->where('user_id', $userId)->where('status', 'concluido'))
            ->exists();
    }
}
