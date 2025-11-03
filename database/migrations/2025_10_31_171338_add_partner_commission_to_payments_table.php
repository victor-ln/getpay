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
            // O Sócio (User) que recebeu a comissão por esta transação
            $table->foreignId('partner_id')
                ->nullable()
                ->after('platform_profit')
                ->constrained('users') // Aponta para a tabela 'users'
                ->onDelete('set null');

            // O valor exato da comissão que o Sócio ganhou
            $table->decimal('partner_commission', 15, 2)
                ->nullable()
                ->after('partner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn(['partner_id', 'partner_commission']);
        });
    }
};
