<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use Carbon\Carbon;

class ReportIncorrectCancellations extends Command
{
    /**
     * A assinatura do comando.
     * Aceita as datas de início e fim como argumentos.
     */
    protected $signature = 'report:incorrect-cancellations {start_date} {end_date}';

    protected $description = 'Gera um relatório de transações canceladas que foram incorretamente marcadas como pagas.';

    /**
     * Executa o comando.
     */
    public function handle()
    {
        $startDate = Carbon::parse($this->argument('start_date'))->startOfDay();
        $endDate = Carbon::parse($this->argument('end_date'))->endOfDay();

        $this->info("A procurar por transações incorretas entre {$startDate->format('d/m/Y')} e {$endDate->format('d/m/Y')}...");

        // A query principal que encontra os pagamentos problemáticos
        $incorrectPayments = Payment::with('account')
            ->where('status', 'paid')
            ->where('type_transaction', 'IN')
            ->where('provider_id', 13)
            ->whereBetween('created_at', [$startDate, $endDate])
            // Esta é a parte "mágica" que olha dentro do JSON
            ->whereJsonContains('provider_response_data->data->status', 'CANCEL') // Adapte 'CANCELLED' se o status for diferente (ex: 'cancel')
            ->get();

        if ($incorrectPayments->isEmpty()) {
            $this->info('Nenhuma transação incorreta encontrada no período especificado.');
            return 0;
        }

        // Agrupa os resultados por conta para o relatório
        $reportByAccount = $incorrectPayments->groupBy('account_id')->map(function ($payments) {
            return [
                'account_name' => $payments->first()->account->name ?? 'Conta Apagada',
                'total_transactions' => $payments->count(),
                'total_amount' => $payments->sum('amount'),
                'total_profit' => ($payments->sum('fee') - $payments->sum('cost')),
            ];
        });

        // --- EXIBIÇÃO DO RELATÓRIO ---
        $this->line("\n========================================================");
        $this->line(" Relatório de Pagamentos Cancelados Marcados como Pagos ");
        $this->line("========================================================");

        // Sumário Geral
        $this->newLine();
        $this->info("Sumário Geral:");
        $this->table(
            [],
            [
                ['Total de Transações Afetadas', $incorrectPayments->count()],
                ['Valor Total Creditado Indevidamente', 'R$ ' . number_format($incorrectPayments->sum('amount'), 2, ',', '.')],
            ]
        );

        // Detalhes por Conta
        $this->newLine();
        $this->info("Detalhes por Conta:");
        $this->table(
            ['Conta do Cliente', 'Nº de Transações', 'Valor Total'],
            $reportByAccount->map(function ($data) {
                return [
                    $data['account_name'],
                    $data['total_transactions'],
                    'R$ ' . number_format($data['total_amount'], 2, ',', '.'),
                    'R$ ' . number_format($data['total_profit'], 2, ',', '.'),
                ];
            })
        );

        $this->newLine();
        $this->comment("Análise concluída.");

        return 0;
    }
}
