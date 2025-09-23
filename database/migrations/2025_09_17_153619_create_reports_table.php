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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Quem solicitou o relatório
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // A qual conta o relatório pertence
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');

            $table->string('file_name'); // Nome do ficheiro gerado
            $table->string('file_path')->nullable(); // Caminho para o ficheiro em disco

            // O "coração" do nosso sistema de feedback
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            $table->timestamp('completed_at')->nullable(); // Quando o job terminou
            $table->text('failure_reason')->nullable(); // Para guardar a mensagem de erro, se falhar

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
