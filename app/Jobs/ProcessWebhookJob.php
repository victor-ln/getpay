<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\{Account, Payment, Bank, User, Balance, BalanceHistory};
use App\Services\AcquirerResolverService;
use App\Services\FeeCalculatorService;
use App\Services\FeeService;
use App\Services\PlatformTransactionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendOutgoingWebhookJob; // Importa o nosso "carteiro"
use Throwable; // Importa Throwable para apanhar todos os erros

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $paymentId;
    protected $webhookPayload;

    /**
     * Create a new job instance.
     * Recebe o ID do pagamento e o payload do webhook (como array).
     */
    public function __construct(int $paymentId, array $webhookPayload)
    {
        $this->paymentId = $paymentId;
        $this->webhookPayload = $webhookPayload;
    }

    /**
     * Execute the job.
     * O Laravel irá injetar automaticamente todos os serviços de que precisamos.
     */
    public function handle(
        AcquirerResolverService $acquirerResolver,
        FeeCalculatorService $feeCalculatorService,
        FeeService $feeService,
        PlatformTransactionService $platformTransactionService
    ): void {
        Log::info("▶️ Iniciando ProcessWebhookJob para Payment ID: {$this->paymentId}");

        try {
            // Busca o pagamento. Usamos 'fresh()' para garantir que pegamos os dados mais recentes.
            $payment = Payment::fresh()->find($this->paymentId);

            if (!$payment) {
                Log::error("❌ ProcessWebhookJob: Payment ID {$this->paymentId} não encontrado. Abortando.");
                return;
            }

            // Re-verifica o status. Se já não estiver pendente/processando, outro processo já o tratou.
            if (!in_array($payment->status, ['pending', 'processing'])) {
                Log::warning("⚠️ ProcessWebhookJob: Payment ID {$this->paymentId} já não estava pendente/processando. (Status: {$payment->status}). Job ignorado.");
                return;
            }

            // Direciona para o handler correto baseado no tipo de transação
            switch ($payment->type_transaction) {
                case 'IN':
                    $this->handlePayinConfirmation($payment, $this->webhookPayload, $acquirerResolver, $feeCalculatorService, $feeService, $platformTransactionService);
                    break;
                case 'OUT':
                    $this->handlePayoutConfirmation($payment, $this->webhookPayload, $acquirerResolver, $feeCalculatorService, $feeService, $platformTransactionService);
                    break;
                default:
                    Log::warning("ProcessWebhookJob: Tipo de transação desconhecido.", ['payment_id' => $payment->id, 'type' => $payment->type_transaction]);
                    break;
            }
        } catch (Throwable $e) {
            Log::error("❌ Erro CRÍTICO no ProcessWebhookJob para Payment ID {$this->paymentId}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Opcional: Atualiza o pagamento para 'failed' em caso de exceção inesperada
            if (isset($payment)) {
                $payment->update(['status' => 'failed', 'provider_response_data' => ['error' => $e->getMessage()]]);
            }

            // Lança a exceção novamente para que a fila possa tentar novamente (retry)
            throw $e;
        }
    }

    /**
     * Lógica de confirmação de PAY-IN (copiada do seu WebhookController).
     */
    private function handlePayinConfirmation(Payment $payment, array $webhookData, AcquirerResolverService $acquirerResolver, FeeCalculatorService $feeCalculatorService, FeeService $feeService, PlatformTransactionService $platformTransactionService)
    {
        $bank = Bank::find($payment->provider_id);
        $acquirerService = $acquirerResolver->resolveByBank($bank);
        $token = $acquirerService->getToken();
        $transactionVerified = $acquirerService->verifyCharge($payment->provider_transaction_id, $token); // Adapte para o seu método

        // Adapte o status de sucesso da sua liquidante
        if (($transactionVerified['data']['status'] ?? null) !== 'FINISHED') {
            Log::info("ProcessWebhookJob: Pay-in #{$payment->id} verificado, mas ainda não está FINISHED.", ['status_api' => $transactionVerified['data']['status'] ?? null]);
            return;
        }

        DB::transaction(function () use ($payment, $transactionVerified, $feeCalculatorService, $feeService, $platformTransactionService) {
            $balance = Balance::firstOrCreate(
                ['account_id' => $payment->account_id, 'acquirer_id' => $payment->provider_id],
                ['available_balance' => 0, 'blocked_balance' => 0]
            );
            $balanceBefore = $balance->available_balance;

            $account = Account::find($payment->account_id);
            $fee = $feeCalculatorService->calculate($account, $payment->amount, 'IN');
            $netAmount = $payment->amount - $fee;
            $cost = $feeService->calculateTransactionCost($payment->provider, 'IN', $payment->amount);

            $payment->status = 'paid';
            $payment->fee = $fee;
            $payment->cost = $cost;
            $payment->platform_profit = (float) ($fee - $cost);
            // ... (preenche outros campos do $payment como end_to_end_id, provider_response_data, etc.)
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
                'description' => 'PIX deposit received'
            ]);

            $platformTransactionService->creditProfitForTransaction($payment);

            Log::info("✅ ProcessWebhookJob: Pay-in #{$payment->id} confirmado e processado com sucesso.");
        });

        // Despacha o Job para notificar o cliente
        SendOutgoingWebhookJob::dispatch($payment->id);
    }

    /**
     * Lógica de confirmação de PAY-OUT (copiada do seu WebhookController).
     */
    private function handlePayoutConfirmation(Payment $payment, array $webhookData, AcquirerResolverService $acquirerResolver, FeeCalculatorService $feeCalculatorService, FeeService $feeService, PlatformTransactionService $platformTransactionService)
    {
        $bank = Bank::find($payment->provider_id);
        $acquirerService = $acquirerResolver->resolveByBank($bank);
        $token = $acquirerService->getToken();
        $transactionVerified = $acquirerService->verifyChargePayOut($payment->provider_transaction_id, $token); // Adapte para o seu método

        $acquirerStatus = $transactionVerified['data']['status'] ?? null;

        if (!in_array($acquirerStatus, ['FINISHED', 'CANCELLED'])) {
            Log::info("ProcessWebhookJob: Pay-out #{$payment->id} verificado, mas com status não tratado.", ['status_api' => $acquirerStatus]);
            return;
        }

        DB::transaction(function () use ($payment, $acquirerStatus, $feeCalculatorService, $feeService, $platformTransactionService) {
            $balance = Balance::where('account_id', $payment->account_id)
                ->where('acquirer_id', $payment->provider_id)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                throw new \Exception("Registo de saldo não encontrado para o pagamento {$payment->id}.");
            }

            $balanceBefore = $balance->available_balance;
            $totalBlockedAmount = $payment->amount + $payment->fee; // O valor que foi bloqueado

            switch ($acquirerStatus) {
                case 'FINISHED':
                    $payment->status = 'paid';
                    $balance->blocked_balance -= $totalBlockedAmount; // Apenas remove do bloqueado
                    $platformTransactionService->creditProfitForTransaction($payment);
                    break;
                case 'CANCELLED':
                    $payment->status = 'cancelled';
                    $balance->blocked_balance -= $totalBlockedAmount; // Remove do bloqueado
                    $balance->available_balance += $totalBlockedAmount; // Devolve para o disponível

                    BalanceHistory::create([
                        'account_id' => $payment->account_id,
                        'acquirer_id' => $payment->provider_id,
                        'payment_id' => $payment->id,
                        'type' => 'credit',
                        'balance_before' => $balanceBefore,
                        'amount' => $totalBlockedAmount,
                        'balance_after' => $balance->available_balance,
                        'description' => 'Reversal for withdrawal ID: ' . $payment->external_payment_id
                    ]);
                    break;
            }

            // ... (atualiza fee, cost, etc. do $payment se necessário)
            $payment->save();
            $balance->save();

            Log::info("✅ ProcessWebhookJob: Pay-out #{$payment->id} reconciliado. Status: {$payment->status}.");
        });

        // Despacha o Job para notificar o cliente
        SendOutgoingWebhookJob::dispatch($payment->id);
    }
}
