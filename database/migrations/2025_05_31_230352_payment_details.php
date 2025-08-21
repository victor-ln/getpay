<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_details', function (Blueprint $table) {
            // O DDL tem `id` varchar(255) NOT NULL PRIMARY KEY.
            // Se for um UUID, use $table->uuid('id')->primary();
            // Se for uma string genérica, use $table->string('id')->primary();
            $table->string('id')->primary();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->json('data'); // DDL diz NOT NULL
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
