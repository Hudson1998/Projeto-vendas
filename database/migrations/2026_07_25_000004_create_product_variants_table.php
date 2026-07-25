<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('tamanho')->nullable();
            $table->string('cor')->nullable();
            $table->string('cor_hex')->nullable();
            $table->unsignedInteger('estoque')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'tamanho', 'cor']);
        });

        $produtos = DB::table('products')->select('id', 'estoque', 'tamanhos', 'cores')->get();

        foreach ($produtos as $produto) {
            $tamanhos = $produto->tamanhos ? json_decode($produto->tamanhos, true) : [];
            $cores = $produto->cores ? json_decode($produto->cores, true) : [];

            $tamanhos = $tamanhos ?: [null];
            $cores = $cores ?: [null];

            $primeira = true;
            foreach ($tamanhos as $tamanho) {
                foreach ($cores as $cor) {
                    DB::table('product_variants')->insert([
                        'product_id' => $produto->id,
                        'tamanho' => $tamanho,
                        'cor' => $cor['nome'] ?? null,
                        'cor_hex' => $cor['hex'] ?? null,
                        'estoque' => $primeira ? $produto->estoque : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $primeira = false;
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
