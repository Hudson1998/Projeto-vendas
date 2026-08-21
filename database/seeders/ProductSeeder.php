<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $produtos = [
            ['nome' => 'Vestido Midi Preto', 'preco' => 189.90, 'categoria' => 'Vestidos', 'imagem' => 'assets/p1.jpg'],
            ['nome' => 'Vestido Longo Cetim', 'preco' => 229.90, 'categoria' => 'Vestidos', 'imagem' => 'assets/p2.jpg'],
            ['nome' => 'Vestido Tubinho', 'preco' => 199.90, 'categoria' => 'Vestidos', 'imagem' => 'assets/p3.jpg'],
            ['nome' => 'Saia Plissada', 'preco' => 159.90, 'categoria' => 'Saias', 'imagem' => 'assets/p4.jpg'],
            ['nome' => 'Saia Lápis Cinza', 'preco' => 139.90, 'categoria' => 'Saias', 'imagem' => 'assets/p5.jpg'],
            ['nome' => 'Camisa de Seda Off-White', 'preco' => 169.90, 'categoria' => 'Camisas', 'imagem' => 'assets/p6.jpg'],
            ['nome' => 'Camisa Alfaiataria', 'preco' => 149.90, 'categoria' => 'Camisas', 'imagem' => 'assets/p7.jpg'],
            ['nome' => 'Calça Pantalona', 'preco' => 179.90, 'categoria' => 'Calças', 'imagem' => 'assets/p8.jpg'],
            ['nome' => 'Bolsa Estruturada', 'preco' => 199.90, 'categoria' => 'Acessórios', 'imagem' => 'assets/p9.jpg'],
            ['nome' => 'Cinto Fivela Dourada', 'preco' => 89.90, 'categoria' => 'Acessórios', 'imagem' => 'assets/p10.jpg'],
        ];

        // updateOrCreate em vez de create: os demais seeders limpam a propria
        // massa antes de inserir, entao so este impedia reexecutar o
        // docker/seed-dev.sh (duplicava os 10 produtos base a cada rodada).
        foreach ($produtos as $produto) {
            Product::updateOrCreate(['nome' => $produto['nome']], $produto);
        }
    }
}
