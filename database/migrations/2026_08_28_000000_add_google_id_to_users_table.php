<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entrada pela conta Google.
     *
     * google_id e o "sub" do token, o identificador estavel da conta -- nao o
     * e-mail, que a pessoa pode trocar. E a senha vira nullable: quem entrou
     * pelo Google nunca escolheu uma, e um hash de string aleatoria so daria a
     * impressao de que existe uma senha para recuperar.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
