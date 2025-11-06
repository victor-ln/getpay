<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\PaymentService; // <-- Importe seu PaymentService
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Para registrar logs

class SubmitPaymentToAcquirerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    public $tries = 3;

    
    public $timeout = 120; 

    /**
     * O objeto do pagamento que este job precisa processar.
     */
    public $payment;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * É AQUI QUE A MÁGICA ACONTECE
     * O Laravel vai injetar seu PaymentService automaticamente.
     */
    public function handle(PaymentService $paymentService): void
    {
        Log::info("[SubmitPaymentJob] Iniciando processamento para Payment ID: {$this->payment->id}");

        try {
            
            
            $response = [
                'success' => true,
                'data' => [
                    'uuid' => 'fake-provider-tx-' . uniqid() 
                ]
            ];

            // 3. Se deu TUDO CERTO, atualize o pagamento:
            $this->payment->status = 'processing'; // ou 'paid', 'sent', etc.
            $this->payment->provider_transaction_id = $response['data']['uuid'] ?? null;
            $this->payment->save();

            Log::info("[SubmitPaymentJob] Sucesso para Payment ID: {$this->payment->id}");

        } catch (\Exception $e) {
            
            // 4. Se algo deu ERRADO:
            Log::error("[SubmitPaymentJob] FALHA para Payment ID: {$this->payment->id}. Erro: " . $e->getMessage());

            // Atualiza o status para 'failed'
            $this->payment->status = 'failed';
            $this->payment->save();

            // Lança a exceção de volta para o Laravel saber que falhou,
            // para que ele possa tentar rodar o job de novo (até 3x)
            throw $e;
        }
    }
}