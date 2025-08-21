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
        Schema::create('fee_tiers', function (Blueprint $table) {
            $table->id();

            // Link para o perfil de taxa ao qual esta faixa pertence
            $table->foreignId('fee_profile_id')->constrained('fee_profiles')->cascadeOnDelete();

            $table->decimal('min_value', 15, 2);
            $table->decimal('max_value', 15, 2)->nullable(); // Nulo significa "acima do valor mínimo"

            // Taxas específicas para esta faixa
            $table->decimal('fixed_fee', 10, 2)->nullable();
            $table->decimal('percentage_fee', 5, 2)->nullable();

            $table->integer('priority')->default(0); // Para desempate, se necessário

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_tiers');
    }
};
