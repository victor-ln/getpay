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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('take_id')
                  ->nullable()
                  ->after('status') // Posiciona a coluna para melhor organização
                  ->constrained('platform_takes') // Aponta para a tabela que criaremos a seguir
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove a chave estrangeira e a coluna, revertendo a alteração
            $table->dropForeign(['take_id']);
            $table->dropColumn('take_id');
        });
    }
};
