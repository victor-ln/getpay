<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->smallInteger('year');
            $table->unsignedTinyInteger('month'); // DDL original tinha smallint, mas tinyint é mais apropriado para mês
            $table->decimal('total_in', 15, 2)->default(0.00);
            $table->decimal('total_out', 15, 2)->default(0.00);
            $table->decimal('net_balance', 15, 2)->storedAs('total_in - total_out'); // Expressão corrigida
            $table->timestamps();
            $table->softDeletes(); // Adicionado para corresponder ao SQL do erro, se intencional

            $table->unique(['user_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_summaries');
    }
};
