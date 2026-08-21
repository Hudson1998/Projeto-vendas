<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductClass extends Model
{
    protected $fillable = [
        'nome',
        'slug',
    ];

    public function subclasses(): HasMany
    {
        return $this->hasMany(ProductSubclass::class);
    }
}
