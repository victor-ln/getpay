<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('url');
            $table->enum('event', ['IN', 'OUT']);
            $table->string('secret_token');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            // $table->timestamp('updated_at')->nullable(); // O DDL original tem isso, mas timestamps() já cobre. Se for intencional, ajuste.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
