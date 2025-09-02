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
        Schema::create('platform_takes', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_profit', 15, 2); // Valor total do lucro retirado
            $table->json('report_data'); // Guarda o relatório detalhado por cliente
            $table->timestamp('start_date'); // Início do período do cálculo
            $table->timestamp('end_date'); // Fim do período (momento do Take)

            // Rastreabilidade da transação financeira
            $table->foreignId('source_bank_id') // Renomeado para clareza
              ->nullable()
              ->constrained('banks') // Aponta para a sua tabela 'banks'
              ->onDelete('set null');
            $table->foreignId('destination_payout_key_id')->nullable()->constrained('payout_destinations')->onDelete('set null');
            $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Status do Payout
            $table->string('payout_status')->default('pending'); // Ex: pending, completed, failed
            $table->string('payout_provider_transaction_id')->nullable(); // ID retornado pelo provedor do PIX
            $table->text('payout_failure_reason')->nullable(); // Mensagem de erro, se houver

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_takes');
    }
};