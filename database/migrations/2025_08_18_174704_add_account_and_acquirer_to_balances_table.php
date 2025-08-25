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
        Schema::table('payments', function (Blueprint $table) {
            // Adiciona a chave estrangeira para a conta.
            // O Laravel automaticamente cria um índice para esta coluna.
            $table->foreignId('account_id')
                ->nullable()
                ->after('id')
                ->constrained('accounts')
                ->onDelete('set null');

            // Adiciona um índice na coluna 'status'.
            // Isso acelera buscas por pagamentos com um status específico (ex: 'pending', 'paid').
            $table->index('status');

            // Adiciona um índice em um possível ID de transação externa.
            // Essencial para localizar rapidamente um pagamento a partir do ID de um gateway.
            $table->index('external_payment_id');
            $table->index('provider_transaction_id');

            // Adiciona um índice composto.
            // Otimiza consultas que filtram por status e ordenam por data,
            // um caso de uso muito comum para exibir transações recentes.
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove a chave estrangeira e a coluna
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
