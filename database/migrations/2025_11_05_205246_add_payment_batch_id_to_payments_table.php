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
            
            // Adiciona a coluna 'payment_batch_id'
            $table->foreignId('payment_batch_id')
                
                // IMPORTANTE: Permite que a coluna seja NULA
                // Isso é necessário para transações antigas ou avulsas (que não são de lote)
                ->nullable() 

                // Opcional: Apenas para organizar, coloca a coluna depois da 'id'
                ->after('id') 

                // Cria a chave estrangeira apontando para a tabela 'payment_batches'
                ->constrained('payment_batches') 

                // BOA PRÁTICA: Se um lote (pai) for deletado,
                // as transações (filhas) não são deletadas, 
                // apenas o 'payment_batch_id' delas vira NULO.
                ->nullOnDelete(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * (Desfaz a alteração, removendo a coluna)
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Para dar rollback, precisamos primeiro remover a chave estrangeira (constraint)
            $table->dropForeign(['payment_batch_id']);

            // Depois, removemos a coluna
            $table->dropColumn('payment_batch_id');
        });
    }
};
