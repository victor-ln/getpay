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
        // Isto funciona perfeitamente em colunas nullable no Postgres
        $table->unique('external_payment_id'); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_payment_id_on_payments', function (Blueprint $table) {
            //
        });
    }
};
