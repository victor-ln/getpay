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
        Schema::table('account_hourly_summaries', function (Blueprint $table) {
            // 1. Remove o índice simples que criamos antes
            // O nome padrão do Laravel é 'tabela_colunas_tipo'
            $table->dropIndex('account_hourly_summaries_account_id_summary_hour_index');
            
            // 2. Adiciona a restrição UNIQUE correta
            $table->unique(['account_id', 'summary_hour']);
        });
    }

    public function down(): void
    {
        Schema::table('account_hourly_summaries', function (Blueprint $table) {
            // Desfaz, caso precise dar rollback
            $table->dropUnique('account_hourly_summaries_account_id_summary_hour_unique');

            // Recria o índice simples
            $table->index(['account_id', 'summary_hour']);
        });
    }
};
