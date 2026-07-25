<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['estoque', 'tamanhos', 'cores']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('estoque')->default(0)->after('descricao');
            $table->json('tamanhos')->nullable()->after('estoque');
            $table->json('cores')->nullable()->after('tamanhos');
        });
    }
};
