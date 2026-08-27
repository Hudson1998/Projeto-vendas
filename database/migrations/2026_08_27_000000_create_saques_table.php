<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saques da carteira da loja.
     *
     * O saldo nao e uma coluna: ele e derivado dos pedidos entregues menos o
     * que ja foi sacado (ver App\Support\CarteiraDaLoja). Guardar um numero
     * mutavel abriria espaco para ele divergir das vendas; guardar os saques
     * mantem a conta sempre reconstruivel a partir dos fatos.
     */
    public function up(): void
    {
        Schema::create('saques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->string('status')->default('solicitado');
            $table->string('destino')->nullable();
            $table->timestamp('processado_em')->nullable();
            $table->timestamps();

            $table->index(['loja_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saques');
    }
};
