<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_subclasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_class_id')->constrained()->cascadeOnDelete();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_subclasses');
    }
};
