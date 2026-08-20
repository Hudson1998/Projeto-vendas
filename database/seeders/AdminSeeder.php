<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@hrmoda.com.br'],
            [
                'name' => 'Administrador HR',
                'password' => 'AdminHR@2026',
                'role' => 'admin',
                'endereco' => null,
            ]
        );
    }
}
