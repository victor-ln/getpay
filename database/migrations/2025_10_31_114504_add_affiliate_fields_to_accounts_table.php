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
            // O código único que esta conta partilha (ex: GETPAY_JOAO)
            $table->string('referral_code')->unique()->nullable()->after('status');

            // A conta que indicou esta conta (a ligação de afiliação)
            $table->foreignId('referred_by_account_id')
                ->nullable()
                ->after('referral_code')
                ->constrained('accounts') // Aponta para a tabela 'accounts'
                ->onDelete('set null'); // Se a conta "mãe" for apagada, mantém o registo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Remove pela ordem inversa da criação
            $table->dropForeign(['referred_by_account_id']);
            $table->dropColumn('referred_by_account_id');
            $table->dropColumn('referral_code');
        });
    }
};
