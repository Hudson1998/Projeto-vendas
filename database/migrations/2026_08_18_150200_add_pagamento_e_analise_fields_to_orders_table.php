<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status_pagamento')->default('pendente')->after('forma_pagamento');
            $table->string('comprovante_pagamento_path')->nullable()->after('status_pagamento');

            $table->string('status_separacao')->nullable()->after('status_pagamento');
            $table->boolean('fragil')->default(false)->after('status_separacao');
            $table->string('dimensoes')->nullable()->after('fragil');

            $table->foreignId('analisado_por')->nullable()->after('dimensoes')->constrained('users')->nullOnDelete();
            $table->timestamp('analisado_em')->nullable()->after('analisado_por');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('analisado_por');
            $table->dropColumn([
                'status_pagamento',
                'comprovante_pagamento_path',
                'status_separacao',
                'fragil',
                'dimensoes',
                'analisado_em',
            ]);
        });
    }
};
