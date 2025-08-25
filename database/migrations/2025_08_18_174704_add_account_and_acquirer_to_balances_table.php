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
