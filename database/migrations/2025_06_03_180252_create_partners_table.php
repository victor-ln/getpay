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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pix_key');
            $table->string('pix_key_type'); // Ex: 'cpf', 'cnpj', 'email', 'phone', 'random'
            $table->decimal('receiving_percentage', 5, 2); // Ex: 50.00 para 50%
            $table->string('withdrawal_frequency'); // Ex: 'daily', 'weekly', 'monthly', 'custom_days'
            $table->integer('custom_withdrawal_days')->nullable(); // Se frequency for 'custom_days', preenche aqui
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // created_at e updated_at
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
