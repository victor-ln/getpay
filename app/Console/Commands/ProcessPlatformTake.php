<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\PlatformTake; // Certifique-se que o nome do seu Model está correto

class ProcessPlatformTake extends Command
{
    /**
     * A assinatura do comando.
     * --from e --to são opcionais para gerar o histórico reverso.
     */
    protected $signature = 'takes:process {--from= : Data de início (Y-m-d H:i:s)} {--to= : Data de fim (Y-m-d H:i:s)}';

    protected $description = 'Calcula o lucro da plataforma e gera um novo registro de "Take"';

    public function handle()
    {

        ini_set('memory_limit', '512M');
        set_time_limit(300);
        $this->info('Iniciando processo de geração de Take...');

        DB::transaction(function () {
            $lastTake = PlatformTake::latest('end_date')->first();

            // 1. Determina o período de cálculo
            $startDate = $this->option('from')
                ? \Carbon\Carbon::parse($this->option('from'))
                : ($lastTake ? $lastTake->end_date : null);

            $endDate = $this->option('to')
                ? \Carbon\Carbon::parse($this->option('to'))
                : now();

            if (!$startDate) {
                $this->warn('Nenhum take anterior encontrado e nenhuma data de início fornecida. Abortando para segurança.');
                return;
            }

            $this->info("Calculando lucro para o período de: {$startDate} até {$endDate}");

            // 2. Busca as transações não processadas no período
            $paymentsToProcess = Payment::where('status', 'paid')
                ->whereNull('take_id')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->get();

            if ($paymentsToProcess->isEmpty()) {
                $this->info('Nenhuma nova transação para processar neste período.');
                return;
            }

            $this->info("Encontradas {$paymentsToProcess->count()} transações para processar.");

            // 3. Calcula os totais detalhados
            $reportData = $paymentsToProcess->groupBy('account_id')->map(function ($paymentsByAccount, $accountId) {

                // Separa as transações por tipo para facilitar os cálculos
                $inPayments = $paymentsByAccount->where('type_transaction', 'IN');
                $outPayments = $paymentsByAccount->where('type_transaction', 'OUT');

                return [
                    'account_id'      => $accountId,
                    'account_name'    => $paymentsByAccount->first()->account->name,

                    // --- Métricas de Lucro (O principal) ---
                    'net_profit'      => $paymentsByAccount->sum('platform_profit'),

                    // --- Contagem de Transações ---
                    'in_transaction_count'  => $inPayments->count(),
                    'out_transaction_count' => $outPayments->count(),

                    // --- Volume Transacionado ---
                    'volume_in'       => $inPayments->sum('amount'),
                    'volume_out'      => $outPayments->sum('amount'),

                    // --- Detalhes Financeiros (a quebra do lucro) ---
                    'fees_in'         => $inPayments->sum('fee'),
                    'costs_in'        => $inPayments->sum('cost'),
                    'fees_out'        => $outPayments->sum('fee'),
                    'costs_out'       => $outPayments->sum('cost'),
                ];
            })->values();

            $take = PlatformTake::create([
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'total_net_profit'  => $paymentsToProcess->sum('platform_profit'),
                'total_volume_in'   => $paymentsToProcess->where('type_transaction', 'IN')->sum('amount'),
                'total_volume_out'  => $paymentsToProcess->where('type_transaction', 'OUT')->sum('amount'),
                'total_fees_in'     => $paymentsToProcess->where('type_transaction', 'IN')->sum('fee'),
                'total_fees_out'    => $paymentsToProcess->where('type_transaction', 'OUT')->sum('fee'),
                'total_costs_in'    => $paymentsToProcess->where('type_transaction', 'IN')->sum('cost'),
                'total_costs_out'   => $paymentsToProcess->where('type_transaction', 'OUT')->sum('cost'),
                'report_data'       => $reportData,
            ]);

            // 4. "Carimba" as transações processadas com o ID do novo take
            $paymentIds = $paymentsToProcess->pluck('id');
            //Payment::whereIn('id', $paymentIds)->update(['take_id' => $take->id]);
            DB::table('payments')->whereIn('id', $paymentIds)->update(['take_id' => $take->id]);

            $this->info("Take #{$take->id} gerado com sucesso! Lucro líquido: R$ " . number_format($take->total_net_profit, 2, ',', '.'));
            $this->info("{$paymentIds->count()} transações foram carimbadas.");
        });
    }
}
