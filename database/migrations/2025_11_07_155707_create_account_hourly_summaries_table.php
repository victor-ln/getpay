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
        Schema::create('account_hourly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');

            // Armazena o início da hora (ex: '2025-11-07 09:00:00')
            $table->dateTime('summary_hour'); 

            // Nossos totais pré-calculados
            $table->decimal('volume_in', 15, 2)->default(0);
            $table->decimal('volume_out', 15, 2)->default(0);
            $table->decimal('total_fees', 15, 2)->default(0);
            $table->decimal('total_costs', 15, 2)->default(0);

            // Índice para buscas rápidas
            $table->index(['account_id', 'summary_hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_hourly_summaries');
    }
};
