<?php

namespace App\Jobs;

use App\Models\Bank;
use App\Models\Payment;
use App\Services\AcquirerResolverService; // <-- Importe seu Resolver
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception; // <-- Importe

class SubmitPaymentToAcquirerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120; // 2 minutos

    public $payment;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment)
    {
        // O Job recebe o pagamento que já foi criado (com status 'pending')
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     * Isto usa a lógica do seu PaymentService, mas adaptada para o Job.
     */
    public function handle(AcquirerResolverService $acquirerResolver): void
    {
        Log::info("[SubmitPaymentJob] Iniciando job para Payment ID: {$this->payment->id}");

        
        if ($this->payment->status !== 'pending') {
            Log::warning("[SubmitPaymentJob] Payment ID: {$this->payment->id} não está 'pending'. Status é {$this->payment->status}. Ignorando.");
            return;
        }

        try {
            
            
            

            
            $user = $this->payment->user;
            if (!$user) {
                throw new Exception("Usuário não encontrado para o Payment ID: {$this->payment->id}");
            }
            
            $account = $user->accounts()->first();
            if (!$account) {
                throw new Exception("Conta não encontrada para o User ID: {$user->id}");
            }

            $bank = Bank::find($this->payment->provider_id);
            if (!$bank) {
                throw new Exception("Banco não encontrado para o Payment ID: {$this->payment->id}");
            }

            $acquirerService = $acquirerResolver->resolveByBank($bank);

            
            $token = $acquirerService->getToken();
            if (!$token) {
                throw new Exception('Falha ao obter token de autenticação');
            }

            
            
            
            $dataForAcquirer = [
                'externalId' => $this->payment->external_payment_id,
                'amount' => $this->payment->amount / 100, 
                'document' => $this->payment->document,
                'name' => $this->payment->name,
                'identification' => $this->payment->identification,
                'expire' => $this->payment->expire,
                'description' => $this->payment->description,
            ];

            
            $response = $acquirerService->createCharge($dataForAcquirer, $token);

            
            if ($response['statusCode'] === 200 || $response['statusCode'] === 201) {
                
                Log::info("[SubmitPaymentJob] Sucesso para Payment ID: {$this->payment->id}");
                
                $this->payment->status = 'pending'; 
                $this->payment->provider_transaction_id = $response['data']['uuid'] ?? null;
                $this->payment->provider_response_data = json_encode($response['data']);
                $this->payment->save();
            } else {
                
                throw new Exception('Falha na liquidante: ' . ($response['data']['message'] ?? 'Erro desconhecido'));
            }

        } catch (Exception $e) {
            
            
            Log::error("[SubmitPaymentJob] FALHA para Payment ID: {$this->payment->id}. Erro: " . $e->getMessage());

            
            $this->payment->status = 'failed';
            $this->payment->provider_response_data = json_encode(['error' => $e->getMessage()]);
            $this->payment->save();

            
            
            throw $e;
        }
    }
}