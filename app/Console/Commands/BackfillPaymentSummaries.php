<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BackfillPaymentSummaries extends Command
{
    /**
     * O nome e a assinatura do comando.
     */
    protected $signature = 'payments:backfill-summaries';

    /**
     * A descrição do comando.
     */
    protected $description = 'Preenche retroativamente a tabela de sumarização horária com todo o histórico de pagamentos.';

    /**
     * Executa o comando.
     */
    public function handle()
    {
        $this->info("Iniciando o backfill dos sumários de pagamento...");

        // 1. Limpa a tabela de sumarização para evitar duplicatas
        $this->warn("Limpando dados antigos da tabela 'account_hourly_summaries'...");
        DB::table('account_hourly_summaries')->truncate();

        // 2. Encontra a data do primeiro pagamento
        $firstPaymentDate = DB::table('payments')->min('created_at');
        if (!$firstPaymentDate) {
            $this->info("Nenhum pagamento encontrado. Nada a fazer.");
            return 0;
        }

        $startDate = Carbon::parse($firstPaymentDate)->startOfDay();
        $endDate = now()->startOfDay(); // Processa até o início de hoje

        $this->info("Período do backfill: " . $startDate->toDateString() . " até " . $endDate->toDateString());

        // 3. Cria um período para iterar dia a dia
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        
        // Cria uma barra de progresso
        $progressBar = $this->output->createProgressBar($period->count());
        $progressBar->start();

        foreach ($period as $date) {
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();

            // 4. Esta é a query de agregação.
            // Ela lê a tabela 'payments' de UM DIA, mas já agrupa por HORA.
            $dailyData = DB::table('payments')
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->select(
                    'account_id',
                    // A mágica está aqui: Agrupa pela HORA.
                    // ATENÇÃO: Sintaxe para PostgreSQL.
                    DB::raw("DATE_TRUNC('hour', created_at) as summary_hour"), 
                    // Se usar MySQL, troque a linha acima por:
                    // DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as summary_hour"),

                    DB::raw("SUM(CASE WHEN type_transaction = 'IN' THEN amount ELSE 0 END) as volume_in"),
                    DB::raw("SUM(CASE WHEN type_transaction = 'OUT' THEN amount ELSE 0 END) as volume_out"),
                    DB::raw("SUM(fee) as total_fees"),
                    DB::raw("SUM(cost) as total_costs")
                )
                ->groupBy('account_id', 'summary_hour')
                ->get();

            // 5. Prepara os dados para inserção
            $dataToInsert = $dailyData->map(function ($item) {
                return [
                    'account_id' => $item->account_id,
                    'summary_hour' => $item->summary_hour,
                    'volume_in' => $item->volume_in,
                    'volume_out' => $item->volume_out,
                    'total_fees' => $item->total_fees,
                    'total_costs' => $item->total_costs,
                ];
            })->toArray();

            // 6. Insere os dados do dia (agrupados por hora) na tabela de sumarização
            if (!empty($dataToInsert)) {
                // Usamos 'upsert' para garantir (caso precise rodar de novo)
                DB::table('account_hourly_summaries')->upsert(
                    $dataToInsert,
                    ['account_id', 'summary_hour'], // Chaves únicas
                    ['volume_in', 'volume_out', 'total_fees', 'total_costs'] // Colunas para atualizar
                );
            }

            // Avança a barra de progresso
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nBackfill concluído com sucesso!");
        return 0;
    }
}