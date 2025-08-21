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
        Schema::create('account_pix_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('type'); // Ex: 'CPF', 'EMAIL', 'PHONE', 'RANDOM'
            $table->string('key');
            $table->string('status')->default('active'); // Pode ser 'active', 'inactive', 'pending_validation'
            $table->timestamps();
            $table->softDeletes(); // Adiciona a coluna 'deleted_at' para o soft delete

            // Garante que um usuário não pode ter a mesma chave ativa duas vezes
            $table->unique(['account_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_pix_keys');
    }
};
