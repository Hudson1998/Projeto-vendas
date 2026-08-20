<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loja_transportadora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loja_id')->constrained('lojas')->cascadeOnDelete();
            $table->foreignId('transportadora_id')->constrained('transportadoras')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['loja_id', 'transportadora_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loja_transportadora');
    }
};
