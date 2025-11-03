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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // A "dona" do produto, como você pediu
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->onDelete('cascade'); // Se a conta for apagada, os seus produtos também são

            $table->string('name');
            $table->text('description')->nullable();

            // Decimal é o tipo correto para guardar dinheiro
            $table->decimal('price', 15, 2)->default(0.00);

            $table->string('status')->default('active'); // Ex: active, inactive

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
