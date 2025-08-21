<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->smallInteger('year'); // DDL original tinha YEAR(4)
            $table->unsignedTinyInteger('week'); // DDL original tinha TINYINT(3) UNSIGNED
            $table->decimal('total_in', 15, 2)->default(0.00);
            $table->decimal('total_out', 15, 2)->default(0.00);
            $table->decimal('net_balance', 15, 2)->storedAs('total_in - total_out'); // Expressão CORRIGIDA
            $table->timestamps();
            $table->softDeletes(); // Adicionado para corresponder ao SQL do erro, se intencional

            $table->unique(['user_id', 'year', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_summaries');
    }
};
