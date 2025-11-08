<?php

namespace App\Console\Commands;

use App\Jobs\SendOutgoingWebhookJob;
use App\Jobs\SendOutgoingWebhookPendingJob;
use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Bank;
use App\Models\Balance;
use App\Models\BalanceHistory;
use App\Models\Account;
use App\Services\AcquirerResolverService;
use App\Services\FeeCalculatorService;
use App\Services\FeeService;
use App\Services\PlatformTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
// Adicione aqui o Job de envio de webhook, se já o tiver
// use App\Jobs\SendOutgoingWebhookJob;

class VerifyPendingPayments extends Command
{
    /**
     * A assinatura do comando. Aceita o ID do banco.
     */
    protected $signature = 'payments:verify-pending {bankId}';

    protected $description = 'Verifica pagamentos IN pendentes de um banco específico e reconcilia o status.';

    // Injeta os serviços necessários
    protected $acquirerResolver;
    protected $feeCalculatorService;
    protected $feeService;
    protected $platformTransactionService;
 
    public function __construct(
        AcquirerResolverService $acquirerResolver,
        FeeCalculatorService $feeCalculatorService,
        FeeService $feeService,
        PlatformTransactionService $platformTransactionService
    ) {
        parent::__construct();
        $this->acquirerResolver = $acquirerResolver;
        $this->feeCalculatorService = $feeCalculatorService;
        $this->feeService = $feeService;
        $this->platformTransactionService = $platformTransactionService;
    }

    /**
     * Executa o comando.
     */
    public function handle()
    {
        $bankId = $this->argument('bankId');
        $bank = Bank::find($bankId);

        if (!$bank) {
            $this->error("Banco com ID {$bankId} não encontrado.");
            return 1;
        }

        $this->info("Iniciando verificação de pagamentos pendentes para o banco: {$bank->name}");

        // Define a janela de tempo (ex: entre 30 mins e 48 horas atrás)
        $startTime = Carbon::now()->subHours(8);
        $endTime = Carbon::now()->subMinutes(30);

        // Busca os pagamentos pendentes
        $pendingPayments = Payment::where('provider_id', $bankId)
            ->where('type_transaction', 'IN')
            ->where('status', 'pending')
            ->whereBetween('created_at', [$startTime, $endTime])
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info('Nenhum pagamento pendente encontrado para verificar.');
            return 0;
        }

        $this->info("Encontrados {$pendingPayments->count()} pagamentos pendentes. A verificar status...");

        // Resolve o serviço da adquirente UMA VEZ
        try {
            $acquirerService = $this->acquirerResolver->resolveByBank($bank);
            $token = $acquirerService->getToken(); // Obtém o token uma vez
        } catch (\Exception $e) {
            $this->error("Erro ao resolver ou autenticar no serviço do banco {$bank->name}: " . $e->getMessage());
            Log::error("Falha ao obter serviço/token para verificação de pendentes do banco {$bankId}", ['exception' => $e]);
            return 1;
        }


        foreach ($pendingPayments as $payment) {
            try {
                $this->line("A verificar Pagamento ID: {$payment->id} (Provider ID: {$payment->provider_transaction_id})");

                // Verifica o status na adquirente
                $transactionVerified = $acquirerService->verifyChargeIn($token, $payment->provider_transaction_id);
                $acquirerStatus = $transactionVerified['data']['status'] ?? null; // Ex: 'FINISHED', 'CANCELLED', 'PENDING'

                $this->info("Status na adquirente: {$acquirerStatus}");

                switch (strtoupper($acquirerStatus)) {
                    case 'CONCLUIDA': // Ou 'PAID', 'COMPLETED', etc. Adapte conforme a sua adquirente
                        $this->info("Processando pagamento confirmado como PAGO...");
                        $this->processPaidPayment($payment, $transactionVerified);
                        $this->info("Pagamento #{$payment->id} confirmado e processado.");
                        break;

                    case 'REMOVIDA_PELO_PSP': // Ou 'FAILED', 'EXPIRED', etc.
                    case 'FAILED':
                        $this->processFailedPayment($payment, $transactionVerified);
                        $this->warn("Pagamento #{$payment->id} marcado como falhado/cancelado.");
                        break;

                    case 'ATIVA':
                    default:
                        // Não faz nada, continua pendente
                        $this->line("Pagamento #{$payment->id} ainda pendente na adquirente.");
                        break;
                }
            } catch (\Exception $e) {
                $this->error("Erro ao verificar o Pagamento ID {$payment->id}: " . $e->getMessage());
                Log::error("Erro durante a verificação do pagamento pendente #{$payment->id}", ['exception' => $e]);
                // Continua para o próximo pagamento
            }
        }

        $this->info('Verificação concluída.');
        return 0;
    }

    /**
     * Processa um pagamento que foi confirmado como PAGO pela adquirente.
     * (Esta lógica é uma réplica do seu handlePayinConfirmation)
     */
    private function processPaidPayment(Payment $payment, array $transactionVerified)
    {
        // Re-verifica o status para evitar race conditions
        if ($payment->fresh()->status !== 'pending') {
            $this->comment("Pagamento #{$payment->id} já não estava pendente. A ignorar.");
            return;
        }

        Log::info("Iniciando processamento do pagamento IN #{$payment->id} para 'paid'.", [
            'transaction_verified' => $transactionVerified
        ]);

        $bank = Bank::find($payment->provider_id);

        DB::transaction(function () use ($payment, $transactionVerified, $bank) {
            $balance = Balance::firstOrCreate(
                ['account_id' => $payment->account_id, 'acquirer_id' => $payment->provider_id],
                ['available_balance' => 0, 'blocked_balance' => 0]
            );
            $balanceBefore = $balance->available_balance;

            $account = Account::find($payment->account_id);
            $fee = $this->feeCalculatorService->calculate($account, $payment->amount, 'IN');
            $netAmount = $payment->amount - $fee;
           // $cost = $this->feeService->calculateTransactionCost($payment->provider()->first(), 'IN', $payment->amount);
            $cost = $this->feeService->calculateTransactionCost($bank, 'IN', $payment->amount);

            $data = $transactionVerified['data'] ?? [];
            $pixData = $data['pix'][0] ?? null;

            $payment->status = 'paid';
            $payment->fee = $fee;
            $payment->cost = $cost;
            $payment->end_to_end_id = $pixData['endToEndId'] ?? '---';
            $payment->provider_response_data = $transactionVerified;
            $payment->name = $pixData['pagador']['nome'] ?? '---';
            $payment->document = $pixData['pagador']['cpf'] ?? $pixData['pagador']['cnpj'] ?? '---';
            $payment->platform_profit = (float) ($fee - $cost);
            $payment->save();

            $balance->available_balance += $netAmount;
            $balance->save();
            $balanceAfter = $balance->available_balance;

            BalanceHistory::create([
                'account_id' => $payment->account_id,
                'acquirer_id' => $payment->provider_id,
                'payment_id' => $payment->id,
                'type' => 'credit',
                'balance_before' => $balanceBefore,
                'amount' => $netAmount,
                'balance_after' => $balanceAfter,
                'description' => 'PIX deposit confirmed by reconciliation', // Descrição diferente
            ]);

            $this->platformTransactionService->creditProfitForTransaction($payment);

            // Despacha o Job para enviar o webhook de saída
            SendOutgoingWebhookPendingJob::dispatch($payment, $transactionVerified);

            Log::info("Pagamento IN #{$payment->id} reconciliado para 'paid'.");
        });
    }

    /**
     * Processa um pagamento que foi confirmado como FALHADO/CANCELADO pela adquirente.
     */
    private function processFailedPayment(Payment $payment, array $transactionVerified)
    {
        // Re-verifica o status para evitar race conditions
        if ($payment->fresh()->status !== 'pending') {
            $this->comment("Pagamento #{$payment->id} já não estava pendente. A ignorar.");
            return;
        }

        // Apenas atualiza o status no nosso sistema
        $payment->status = 'cancelled'; // Ou 'cancelled', conforme preferir
        $payment->provider_response_data = $transactionVerified;
        $payment->save();

        Log::info("Pagamento IN #{$payment->id} reconciliado para 'failed/cancelled'.");

        // (Opcional) Despacha um Job para enviar o webhook de falha para o cliente
        // SendOutgoingWebhookJob::dispatch($payment->account_id, $payment, $transactionVerified);
    }
}
