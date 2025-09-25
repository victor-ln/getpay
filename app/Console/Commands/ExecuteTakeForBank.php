<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\PlatformTake;
use App\Models\Bank;
use App\Models\PayoutDestination;
use App\Jobs\ProcessTakePayoutJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExecuteTakeForBank extends Command
{
    /**
     * O nome e a assinatura do comando da consola.
     * Adicionamos {bankId} para receber o ID do banco.
     */
    protected $signature = 'takes:execute-for-bank {bankId}';

    protected $description = 'Calcula o lucro de um banco específico e inicia a retirada (Take).';

    /**
     * Executa o comando da consola.
     */
    public function handle()
    {
        $bankId = $this->argument('bankId');
        $bank = Bank::find($bankId);

        if (!$bank) {
            $this->error("Banco com ID {$bankId} não encontrado.");
            return 1; // Código de erro
        }

        $this->info("Iniciando processo de Take para o banco: {$bank->name} (ID: {$bankId})");

        // Usamos uma transação de base de dados para garantir a integridade
        DB::transaction(function () use ($bank, $bankId) {

            // 1. Encontra a data de corte: a data do último pagamento deste banco que já foi processado.
            $lastProcessedPaymentDate = Payment::where('provider_id', $bankId)
                ->whereNotNull('take_id')
                ->latest('created_at')
                ->first()?->created_at ?? '1970-01-01';

            $this->info("Última transação processada para este banco em: " . $lastProcessedPaymentDate);

            // 2. Busca os pagamentos pendentes para este banco específico
            $pendingPayments = Payment::where('provider_id', $bankId)
                ->whereNull('take_id')
                ->where('status', 'paid')
                ->where('created_at', '>', $lastProcessedPaymentDate)
                ->get();

            if ($pendingPayments->isEmpty()) {
                $this->info('Nenhuma nova transação para processar para este banco.');
                return;
            }

            $this->info("Encontradas {$pendingPayments->count()} transações para processar.");

            // 3. Calcula os totais e o relatório
            $reportData = $this->generateDetailedReport($pendingPayments);
            $summary = $this->calculateSummary($pendingPayments);
            $totalProfit = $summary['total_net_profit'];

            if ($totalProfit <= 0) {
                $this->info('Lucro zero ou negativo. Nenhum saque será iniciado.');
                return;
            }

            // 4. Cria o registo do "Take"
            $take = PlatformTake::create([
                'start_date'        => $pendingPayments->min('created_at'),
                'end_date'          => now(),
                'total_net_profit'  => $totalProfit,
                'report_data'       => $reportData,
                'payout_status'     => 'processing',
                'executed_by_user_id' => 1, // ID do utilizador do sistema
                'total_volume_in'   => $summary['total_volume_in'],
                'total_volume_out'  => $summary['total_volume_out'],
                'total_fees_in'     => $summary['total_fees_in'],
                'total_fees_out'    => $summary['total_fees_out'],
                'total_costs_in'    => $summary['total_costs_in'],
                'total_costs_out'   => $summary['total_costs_out'],
            ]);

            // 5. "Carimba" os pagamentos
            Payment::whereIn('id', $pendingPayments->pluck('id'))->update(['take_id' => $take->id]);

            // 6. Encontra o destino do saque e despacha o Job
            $destination = PayoutDestination::where('is_default_take_destination', true)->first();
            if (!$destination) {
                $this->error("Nenhum destino de saque padrão encontrado. O saque não foi iniciado.");
                Log::error("Take #{$take->id} criado, mas saque não iniciado por falta de destino padrão.");
                return;
            }

            ProcessTakePayoutJob::dispatch(
                $take->id,
                $bank->id,
                $destination->id,
                $totalProfit
            );

            $this->info("Take #{$take->id} gerado com sucesso para o banco {$bank->name}.");
            $this->info("Lucro a ser sacado: R$ " . number_format($totalProfit, 2, ',', '.'));
            $this->info("Job de saque despachado para a fila.");
        });
    }

    // Funções auxiliares para manter o código limpo
    private function generateDetailedReport($payments)
    {
        return $payments->groupBy('account_id')->map(function ($paymentsByAccount) {
            return [
                'account_name' => $paymentsByAccount->first()->account->name ?? 'Conta Apagada',
                'total_in'     => $paymentsByAccount->where('type_transaction', 'IN')->sum('amount'),
                'total_fee'    => $paymentsByAccount->sum('fee'),
                'total_cost'   => $paymentsByAccount->sum('cost'),
            ];
        })->values();
    }

    private function calculateSummary($payments)
    {
        return [
            'total_net_profit'  => $payments->sum('platform_profit'),
            'total_volume_in'   => $payments->where('type_transaction', 'IN')->sum('amount'),
            'total_volume_out'  => $payments->where('type_transaction', 'OUT')->sum('amount'),
            'total_fees_in'     => $payments->where('type_transaction', 'IN')->sum('fee'),
            'total_fees_out'    => $payments->where('type_transaction', 'OUT')->sum('fee'),
            'total_costs_in'    => $payments->where('type_transaction', 'IN')->sum('cost'),
            'total_costs_out'   => $payments->where('type_transaction', 'OUT')->sum('cost'),
        ];
    }
}
