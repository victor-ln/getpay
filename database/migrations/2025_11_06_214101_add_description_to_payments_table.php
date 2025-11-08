<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Adiciona a coluna 'description'
            // ->nullable() torna o campo opcional (recomendado para colunas novas)
            // ->after('amount') coloca a coluna depois da coluna 'amount' (opcional, ajuste conforme precisar)
            $table->string('description')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove a coluna caso precise reverter a migration
            $table->dropColumn('description');
        });
    }
};
