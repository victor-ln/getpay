<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Para generated column com expressão

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->decimal('total_in', 15, 2)->default(0.00);
            $table->decimal('total_out', 15, 2)->default(0.00);
            $table->decimal('net_balance', 15, 2)->storedAs('total_in - total_out'); // <--- CORRIGIDO AQUI
            $table->timestamps();
            $table->softDeletes(); // Você adicionou, o DDL original não tinha para daily_balances
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_balances');
    }
};
