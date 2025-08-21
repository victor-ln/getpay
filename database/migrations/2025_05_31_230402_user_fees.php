<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Assumindo onDelete cascade
            $table->foreignId('fee_id')->constrained('fees')->onDelete('cascade');   // Assumindo onDelete cascade
            $table->boolean('is_default')->default(false)->comment('Se esta é a taxa padrão para o usuário');
            $table->enum('type', ['IN', 'OUT'])->nullable();
            $table->enum('status', ['0', '1'])->default('1');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_fees');
    }
};
