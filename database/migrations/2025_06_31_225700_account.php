<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Sócio que indicou a conta (relação com a tabela users)
            $table->foreignId('partner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('status')->default(true);

            // Adquirente padrão para esta conta (relação com a tabela acquirers/banks)
            $table->foreignId('acquirer_id')->nullable()->constrained('banks')->onDelete('set null');

            // Configurações financeiras que antes pertenciam ao User
            $table->decimal('min_amount_transaction', 10, 2)->default(1.00);
            $table->decimal('max_amount_transaction', 10, 2)->default(5000.00);

            $table->timestamps();
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
