<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('forma_pagamento')->nullable()->after('total');
            $table->string('tipo_entrega')->nullable()->after('forma_pagamento');
            $table->string('endereco_entrega')->nullable()->after('tipo_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['forma_pagamento', 'tipo_entrega', 'endereco_entrega']);
        });
    }
};
