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
        Schema::create('account_fee_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('fee_profile_id')->constrained('fee_profiles')->cascadeOnDelete();

            // Coluna que define para qual tipo de transação esta regra se aplica
            $table->string('transaction_type')->default('DEFAULT'); // Valores: IN, OUT, DEFAULT
            $table->string('status')->default('active');

            $table->timestamps();

            // Garante que uma conta só pode ter uma regra por tipo de transação
            $table->unique(['account_id', 'transaction_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_fee_profile');
    }
};
