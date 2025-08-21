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
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->enum('transaction_type', ['IN', 'OUT']);
            $table->unsignedInteger('priority')->comment('Ordem de execução. Menor = primeiro.');

            $table->enum('payee_type', ['platform', 'referring_partner', 'profit_sharing_partner']);

            // O ID do sócio, aplicável para 'referring_partner' e 'profit_sharing_partner'
            $table->foreignId('partner_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->enum('value_type', ['percentage', 'fixed']);
            $table->decimal('value', 10, 4)->comment('Valor percentual (ex: 0.02) ou fixo (ex: 0.50)');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
