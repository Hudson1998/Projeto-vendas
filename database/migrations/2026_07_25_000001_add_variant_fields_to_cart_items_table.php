<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('tamanho')->nullable()->after('product_id');
            $table->string('cor')->nullable()->after('tamanho');
            $table->unique(['user_id', 'product_id', 'tamanho', 'cor']);
        });

        // MySQL won't drop an index while it's the only one backing a FK,
        // so the new composite unique above must exist before this drops the old one.
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_id_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id', 'tamanho', 'cor']);
            $table->dropColumn(['tamanho', 'cor']);
        });
    }
};
