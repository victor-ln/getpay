<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->comment('IN para entrada, OUT para saída');
            // Se 'type' tiver valores fixos, considere: $table->enum('type', ['IN', 'OUT'])->comment(...);
            $table->decimal('percentage', 8, 4)->comment('Percentual da taxa (ex: 10.0000 para 10%)');
            $table->decimal('minimum_fee', 10, 2)->comment('Valor mínimo da taxa em BRL');
            $table->decimal('fixed_fee', 10, 2)->nullable()->comment('Valor fixo da taxa em BRL');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
