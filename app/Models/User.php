<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'endereco',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLojista(): bool
    {
        return $this->role === 'lojista';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function lojistaProfile(): HasOne
    {
        return $this->hasOne(LojistaProfile::class);
    }

    public function initials(): string
    {
        $partes = preg_split('/\s+/', trim($this->name));
        $primeira = mb_substr($partes[0] ?? '', 0, 1);
        $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';

        return mb_strtoupper($primeira.$ultima);
    }
}
