<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('distancia_km', 6, 2)->nullable()->after('total');
            $table->decimal('valor_frete', 8, 2)->nullable()->after('distancia_km');

            $table->boolean('entrega_propria')->nullable()->after('status_separacao');
            $table->foreignId('transportadora_id')->nullable()->after('entrega_propria')->constrained('transportadoras')->nullOnDelete();
            $table->foreignId('motorista_id')->nullable()->after('transportadora_id')->constrained('motoristas')->nullOnDelete();

            $table->string('ip_compra', 45)->nullable()->after('comprovante_pagamento_path');
            $table->string('localizacao')->nullable()->after('ip_compra');
            $table->string('codigo_pagamento')->nullable()->after('localizacao');
            $table->boolean('verificado_banco')->default(false)->after('codigo_pagamento');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transportadora_id');
            $table->dropConstrainedForeignId('motorista_id');
            $table->dropColumn([
                'distancia_km',
                'valor_frete',
                'entrega_propria',
                'ip_compra',
                'localizacao',
                'codigo_pagamento',
                'verificado_banco',
            ]);
        });
    }
};
