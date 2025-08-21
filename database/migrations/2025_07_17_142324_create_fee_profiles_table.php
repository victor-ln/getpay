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
        Schema::create('fee_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Plano Padrão", "Cliente A Fixo"
            $table->text('description')->nullable();

            // Coluna chave que define a estratégia de cálculo
            $table->string('calculation_type'); // Valores: 'SIMPLE_FIXED', 'GREATER_OF_BASE_PERCENTAGE', 'TIERED'

            // Campos para os tipos de cálculo mais simples
            $table->decimal('fixed_fee', 10, 2)->nullable();
            $table->decimal('base_fee', 10, 2)->nullable();
            $table->decimal('percentage_fee', 5, 2)->nullable(); // Ex: 3.50 para 3.5%

            $table->timestamps();
            $table->softDeletes(); // Adicionando soft delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_profiles');
    }
};
