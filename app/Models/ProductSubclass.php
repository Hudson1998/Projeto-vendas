<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSubclass extends Model
{
    protected $fillable = [
        'product_class_id',
        'nome',
        'slug',
    ];

    public function productClass(): BelongsTo
    {
        return $this->belongsTo(ProductClass::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
