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
        Schema::table('payout_destinations', function (Blueprint $table) {
            // Nova coluna booleana, que por defeito é 'false'
            $table->boolean('is_default_for_takeouts')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payout_destinations', function (Blueprint $table) {
            //
        });
    }
};
