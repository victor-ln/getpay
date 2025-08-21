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
        Schema::create('account_partner_commission', function (Blueprint $table) {
            $table->id();

            // Vínculo com a conta
            $table->foreignId('account_id')->constrained()->onDelete('cascade');

            // Vínculo com o sócio (que é um usuário)
            $table->foreignId('partner_id')->constrained('users')->onDelete('cascade');

            // A comissão que o sócio ganha sobre o lucro da conta
            $table->decimal('commission_rate', 5, 4)->comment('Ex: 0.2500 para 25%');

            // A taxa que a plataforma cobra do sócio no saque/repasse
            $table->decimal('platform_withdrawal_fee_rate', 5, 4)->default(0.00)->comment('Ex: 0.1000 para 10%');

            $table->timestamps();

            // Garante que a mesma combinação de conta e sócio não se repita
            $table->unique(['account_id', 'partner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_partner_commission');
    }
};
