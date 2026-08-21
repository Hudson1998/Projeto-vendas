<?php

namespace App\Models;

use App\Classes\Loja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'loja_id',
        'nome',
        'categoria',
        'product_subclass_id',
        'preco',
        'preco_promocional',
        'imagem',
        'descricao',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'preco_promocional' => 'decimal:2',
    ];

    public function emPromocao(): bool
    {
        return $this->preco_promocional !== null && $this->preco_promocional < $this->preco;
    }

    public function loja(): BelongsTo
    {
        return $this->belongsTo(Loja::class);
    }

    public function subclass(): BelongsTo
    {
        return $this->belongsTo(ProductSubclass::class, 'product_subclass_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function classe(): ?ProductClass
    {
        return $this->subclass?->productClass;
    }

    public function estoqueTotal(): int
    {
        return $this->variants->sum('estoque');
    }

    /**
     * @return array<int, string>
     */
    public function tamanhosDisponiveis(): array
    {
        return $this->variants
            ->pluck('tamanho')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{nome: string, hex: ?string}>
     */
    public function coresDisponiveis(): array
    {
        return $this->variants
            ->filter(fn (ProductVariant $variante) => $variante->cor !== null)
            ->unique('cor')
            ->values()
            ->map(fn (ProductVariant $variante) => ['nome' => $variante->cor, 'hex' => $variante->cor_hex])
            ->all();
    }

    public function variantePara(?string $tamanho, ?string $cor): ?ProductVariant
    {
        return $this->variants->first(
            fn (ProductVariant $variante) => $variante->tamanho === $tamanho && $variante->cor === $cor
        );
    }

    public function purchasedBy(int $userId): bool
    {
        return OrderItem::where('product_id', $this->id)
            ->whereHas('order', fn ($query) => $query->where('user_id', $userId)->where('status', 'concluido'))
            ->exists();
    }
}
