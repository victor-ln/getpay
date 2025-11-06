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
        Schema::create('payment_batches', function (Blueprint $table) {
            $table->id();

            // Foreign key para o admin que criou o lote
            $table->foreignId('user_id')->constrained('users'); 

            // Foreign key para a liquidante (ex: 'acquirers')
            // (Ajuste 'acquirers' se o nome da sua tabela for outro)
            $table->foreignId('acquirer_id')->constrained('banks');
            
            // Valor total em CENTAVOS (ex: R$ 80.000,00 será salvo como 8000000)
            // Usar bigInteger é mais seguro para valores financeiros.
            $table->bigInteger('total_amount'); 

            $table->integer('number_of_splits')->default(1);
            
            // Status do lote: pending, processing, completed
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_batches');
    }
};
