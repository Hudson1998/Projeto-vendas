<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lojas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Identificação
            $table->enum('tipo_pessoa', ['fisica', 'juridica']);
            $table->string('cpf', 14)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('razao_social')->nullable();
            $table->string('nome_fantasia');
            $table->string('nome_exibicao')->nullable();
            $table->string('inscricao_estadual')->nullable();
            $table->boolean('ie_isento')->default(false);
            $table->string('cnae')->nullable();
            $table->string('regime_tributario')->nullable();

            // Contato
            $table->string('nome_responsavel');
            $table->string('email')->nullable();
            $table->string('whatsapp');

            // Endereço fiscal
            $table->string('fiscal_cep', 9);
            $table->string('fiscal_rua');
            $table->string('fiscal_numero');
            $table->string('fiscal_complemento')->nullable();
            $table->string('fiscal_bairro');
            $table->string('fiscal_cidade');
            $table->string('fiscal_estado', 2);

            // Endereço de envio (expedição dos pedidos)
            $table->string('envio_cep', 9)->nullable();
            $table->string('envio_rua')->nullable();
            $table->string('envio_numero')->nullable();
            $table->string('envio_complemento')->nullable();
            $table->string('envio_bairro')->nullable();
            $table->string('envio_cidade')->nullable();
            $table->string('envio_estado', 2)->nullable();

            // Dados bancários / recebimento
            $table->string('banco')->nullable();
            $table->string('agencia')->nullable();
            $table->string('conta')->nullable();
            $table->string('titular_conta')->nullable();
            $table->string('chave_pix')->nullable();

            // Vitrine
            $table->string('logotipo')->nullable();
            $table->text('bio_loja');

            // Operação
            $table->unsignedSmallInteger('prazo_expedicao_dias_uteis');
            $table->text('politica_troca_devolucao');

            // Documentação (KYC)
            $table->string('documento_identidade_path');
            $table->string('selfie_documento_path');
            $table->string('contrato_social_mei_path')->nullable();
            $table->string('comprovante_endereco_path');
            $table->string('comprovante_cnpj_path')->nullable();

            // Acesso e assinatura
            $table->string('nivel_acesso')->default('padrao');
            $table->foreignId('plano_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lojas');
    }
};
