<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->uuid('uuid')->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('document', 20)->nullable()->unique();
            $table->string('role')->default('user');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps(); // created_at, updated_at
            $table->enum('level', ['admin', 'client', 'partner'])->default('client');
            $table->enum('status', ['0', '1'])->default('1'); // '0' para inativo, '1' para ativo
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
