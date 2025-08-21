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
            // Coluna específica para o EndToEndId, indexada para buscas rápidas
            $table->string('end_to_end_id')->nullable()->after('provider_transaction_id')->index();

            // Coluna genérica para o payload completo, para auditoria e flexibilidade
            $table->json('provider_response_data')->nullable()->after('end_to_end_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};
