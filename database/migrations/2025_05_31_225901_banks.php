<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token')->nullable();
            $table->string('user')->nullable();
            $table->string('password')->nullable(); // Considere criptografar se for senha sensível
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable(); // Considere criptografar
            $table->string('baseurl')->nullable();
            $table->json('config')->nullable();
            $table->json('fees_config')->nullable();
            $table->boolean('active')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
