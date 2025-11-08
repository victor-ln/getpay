<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SummarizeHourlyPayments extends Command
{
   protected $signature = 'payments:summarize-hourly';
   protected $description = 'Summarizes payments data for the last hour';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Define o período (a hora anterior)
        $endOfHour = now()->startOfHour(); // Ex: 10:00:00
        $startOfHour = $endOfHour->copy()->subHour(); // Ex: 09:00:00

        // 2. A ÚNICA query pesada do sistema
        // (Note que estamos lendo 'payments', mas apenas 1 hora de dados)
        $hourlyData = DB::table('payments')
            ->where('created_at', '>=', $startOfHour)
            ->where('created_at', '<', $endOfHour)
            ->select(
                'account_id',
                DB::raw("SUM(CASE WHEN type_transaction = 'IN' THEN amount ELSE 0 END) as volume_in"),
                DB::raw("SUM(CASE WHEN type_transaction = 'OUT' THEN amount ELSE 0 END) as volume_out"),
                DB::raw("SUM(fee) as total_fees"),
                DB::raw("SUM(operational_cost) as total_costs")
            )
            ->groupBy('account_id')
            ->get();

        if ($hourlyData->isEmpty()) {
            $this->info("Nenhum dado encontrado para $startOfHour.");
            return;
        }

        // 3. Prepara os dados para inserir na tabela de sumarização
        $summaryData = $hourlyData->map(function ($item) use ($startOfHour) {
            return [
                'account_id' => $item->account_id,
                'summary_hour' => $startOfHour, // Marca que esses dados são das 09:00
                'volume_in' => $item->volume_in,
                'volume_out' => $item->volume_out,
                'total_fees' => $item->total_fees,
                'total_costs' => $item->total_costs,
            ];
        })->toArray();

        // 4. Insere os dados na tabela nova
        // (updateOrCreate garante que se o job rodar 2x, ele não duplica)
        foreach ($summaryData as $data) {
            DB::table('account_hourly_summaries')->updateOrCreate(
                [
                    'account_id' => $data['account_id'],
                    'summary_hour' => $data['summary_hour'],
                ],
                $data
            );
        }

        $this->info("Sumarização da hora $startOfHour concluída!");
    }

    
}
