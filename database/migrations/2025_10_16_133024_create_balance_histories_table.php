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
        Schema::create('balance_histories', function (Blueprint $table) {
            $table->id();

            // Chaves para identificar a "carteira" exata que foi movimentada
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('acquirer_id')->constrained('banks')->onDelete('cascade');

            // ✅ A sua sugestão: link para a transação que originou a movimentação
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');

            // O tipo de movimentação para clareza no extrato
            $table->enum('type', ['credit', 'debit']);

            // O "livro-razão": Saldo Antes -> Movimentação -> Saldo Depois
            $table->decimal('balance_before', 15, 2);
            $table->decimal('amount', 15, 2); // O valor líquido da movimentação
            $table->decimal('balance_after', 15, 2);

            $table->string('description'); // Descrição da operação para o extrato do cliente

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_histories');
    }
};
