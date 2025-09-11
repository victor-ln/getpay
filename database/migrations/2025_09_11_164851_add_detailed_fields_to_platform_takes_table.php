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
        Schema::table('platform_takes', function (Blueprint $table) {
            // Renomeia a coluna antiga para maior clareza
            $table->renameColumn('total_profit', 'total_net_profit');

            // Adiciona as novas colunas de resumo detalhado
            $table->decimal('total_volume_in', 15, 2)->default(0)->after('total_net_profit');
            $table->decimal('total_volume_out', 15, 2)->default(0)->after('total_volume_in');
            $table->decimal('total_fees_in', 15, 2)->default(0)->after('total_volume_out');
            $table->decimal('total_fees_out', 15, 2)->default(0)->after('total_fees_in');
            $table->decimal('total_costs_in', 15, 2)->default(0)->after('total_fees_out');
            $table->decimal('total_costs_out', 15, 2)->default(0)->after('total_costs_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_takes', function (Blueprint $table) {
            $table->renameColumn('total_net_profit', 'total_profit');

            $table->dropColumn([
                'total_volume_in',
                'total_volume_out',
                'total_fees_in',
                'total_fees_out',
                'total_costs_in',
                'total_costs_out',
            ]);
        });
    }
};
