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
        Schema::table('banks', function (Blueprint $table) {
            // Nova coluna JSON que pode ser nula.
            // Para os seus bancos antigos, este campo ficará simplesmente NULL.
            // Para o novo banco E2, vamos preenchê-lo.
            $table->json('api_config')->nullable()->after('baseurl');
        });
    }

    /**
     * Reverse the migrations.
     * O método down() garante que a migration pode ser revertida com segurança.
     */
    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn('api_config');
        });
    }
};
