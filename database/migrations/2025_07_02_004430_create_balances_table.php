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
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            // Link com a tabela de usuários. Ao deletar um usuário, o saldo dele também é deletado.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Usamos 'decimal' para valores monetários para evitar problemas de precisão.
            // 15 dígitos no total, 2 após a vírgula.
            $table->decimal('available_balance', 15, 2)->default(0.00);
            $table->decimal('blocked_balance', 15, 2)->default(0.00);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};
