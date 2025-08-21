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
        Schema::create('partner_payout_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->onDelete('cascade');
            $table->string('pix_key_type'); // Ex: 'CPF', 'EMAIL', 'PHONE'
            $table->string('pix_key');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Um sócio não deve ter a mesma chave PIX cadastrada duas vezes.
            $table->unique(['partner_id', 'pix_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_payout_methods');
    }
};
