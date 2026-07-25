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
        'imagem',
        'descricao',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
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
