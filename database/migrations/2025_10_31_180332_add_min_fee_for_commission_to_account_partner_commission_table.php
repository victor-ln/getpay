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
        Schema::table('account_partner_commission', function (Blueprint $table) {
            // Adiciona a nova coluna para o "piso" da taxa
            // O default(0.00) garante que as regras antigas continuem a funcionar
            // até você definir um valor específico para elas.
            $table->decimal('min_fee_for_commission', 15, 2)
                ->default(0.00)
                ->after('platform_withdrawal_fee_rate'); // Coloca-a depois das outras taxas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_partner_commission', function (Blueprint $table) {
            $table->dropColumn('min_fee_for_commission');
        });
    }
};
