<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Ou a ação desejada em onDelete
            $table->integer('provider_id')->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('external_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->enum('type_transaction', ['IN', 'OUT', 'Refund']);
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

            // Índices do DDL original
            // $table->index('user_id'); // foreignId já cria um índice por padrão
            $table->index('status');
            $table->index(['type_transaction', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
