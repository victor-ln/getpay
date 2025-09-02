<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payout_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('nickname'); // Apelido para fácil identificação (ex: "Conta Matriz Inter")
            $table->string('pix_key_type'); // Ex: 'CNPJ', 'Email', 'Telefone', 'Aleatoria'
            $table->string('pix_key'); // A chave PIX em si
            $table->string('owner_name'); // Nome do titular da chave
            $table->string('owner_document'); // CPF/CNPJ do titular
            $table->boolean('is_active')->default(true); // Permite desativar uma chave sem apagá-la
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_destinations');
    }
};