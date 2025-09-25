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
        Schema::create('scheduled_takes', function (Blueprint $table) {
            $table->id();

            // Para qual banco este agendamento se aplica
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');

            // A frequência da execução (guardamos o nome do método do Laravel)
            $table->string('frequency');

            // Um campo opcional para parâmetros (ex: para 'dailyAt', guardamos '17:00')
            $table->string('frequency_parameters')->nullable();

            // A "chave" para ligar e desligar o agendamento
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_takes');
    }
};
