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

            // Adiciona a coluna para o nome do pagador
            $table->string('name')->nullable()->after('provider_response_data');

            // Adiciona a coluna para o documento do pagador
            $table->string('document')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Remove as colunas caso a migration seja revertida.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['provider_response_data', 'name', 'document']);
        });
    }
};
