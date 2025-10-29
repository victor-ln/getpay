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
        Schema::table('accounts', function (Blueprint $table) {
            // O ID do cliente, deve ser único e indexado para buscas rápidas
            $table->string('api_client_id')->unique()->nullable()->after('user_id');

            // O segredo do cliente, guardado como hash por segurança
            $table->string('api_client_secret')->nullable()->after('api_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['api_client_id', 'api_client_secret']);
        });
    }
};
