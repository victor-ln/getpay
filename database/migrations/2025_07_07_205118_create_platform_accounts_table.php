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
        Schema::create('platform_accounts', function (Blueprint $table) {
            $table->id();

            // Chave estrangeira para associar esta conta a um Banco/Adquirente
            $table->foreignId('bank_id')->unique()->constrained()->onDelete('cascade');

            // Nome descritivo, ex: "Caixa Geral Truztpix"
            $table->string('account_name');

            // O saldo atual desta conta, que será atualizado a cada transação
            $table->decimal('current_balance', 15, 2)->default(0.00);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_accounts');
    }
};
