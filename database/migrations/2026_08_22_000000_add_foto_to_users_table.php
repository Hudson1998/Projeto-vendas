<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // caminho relativo a public/, no mesmo formato de products.imagem
            // ("uploads/arquivo.ext"). Nullable: a foto e opcional e quem nao
            // enviar continua aparecendo pelo monograma das iniciais.
            $table->string('foto')->nullable()->after('endereco');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
