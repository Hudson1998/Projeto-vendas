<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lojista_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('telefone');
            $table->string('nome_fantasia');
            $table->enum('tipo_pessoa', ['fisica', 'juridica']);
            $table->string('cpf', 14)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('razao_social')->nullable();
            $table->string('inscricao_estadual')->nullable();
            $table->boolean('ie_isento')->default(false);
            $table->string('logotipo')->nullable();
            $table->text('descricao_loja');
            $table->string('cep', 9);
            $table->string('rua');
            $table->string('numero');
            $table->string('complemento')->nullable();
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado', 2);
            $table->unsignedSmallInteger('prazo_expedicao_dias_uteis');
            $table->text('politica_troca_devolucao');
            $table->string('doc_identidade_path');
            $table->string('selfie_documento_path');
            $table->string('contrato_social_mei_path')->nullable();
            $table->string('comprovante_endereco_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lojista_profiles');
    }
};
