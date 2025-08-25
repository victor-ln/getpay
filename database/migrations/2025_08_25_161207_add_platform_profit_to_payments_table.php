<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adiciona a coluna 'platform_profit' à tabela 'payments'.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Adiciona a coluna para o lucro da plataforma
            // decimal(coluna, total de dígitos, casas decimais)
            // O valor padrão 0 é importante para evitar erros em cálculos futuros.
            $table->decimal('platform_profit', 10, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Remove a coluna caso a migration seja revertida.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('platform_profit');
        });
    }
};
