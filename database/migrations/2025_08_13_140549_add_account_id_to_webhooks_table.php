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
        Schema::table('webhooks', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->constrained('accounts') // Cria a chave estrangeira para a tabela 'accounts'
                ->onDelete('cascade'); // Se uma conta for deletada, seus webhooks também serão.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            //
        });
    }
};
