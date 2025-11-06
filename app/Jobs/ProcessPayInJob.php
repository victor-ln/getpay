<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Payment;
use App\Models\Bank;
use App\Services\AcquirerResolverService;
use Illuminate\Support\Facades\Log;
use Throwable;

use App\Jobs\SendOutgoingWebhookJob;

class ProcessPayInJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $paymentId;
    protected $requestData;

    /**
     * Create a new job instance.
     * Recebe o ID do pagamento e os dados originais da requisição.
     */
    public function __construct(int $paymentId, array $requestData)
    {
        $this->paymentId = $paymentId;
        $this->requestData = $requestData;
    }

    /**
     * Execute the job.
     */
    public function handle(AcquirerResolverService $acquirerResolver): void
    {
        Log::info("▶️ Iniciando ProcessPayInJob para Payment ID: {$this->paymentId}");

        $payment = Payment::find($this->paymentId);

        if (!$payment) {
            Log::error("❌ ProcessPayInJob: Payment ID {$this->paymentId} não encontrado.");
            return;
        }

        // Garante que o job não processa um pagamento que já não esteja pendente
        if ($payment->status !== 'pending') {
            Log::warning("⚠️ ProcessPayInJob: Payment ID {$this->paymentId} já não está pendente (Status: {$payment->status}). Job ignorado.");
            return;
        }

        try {
            // 1. Encontra o banco (liquidante) correto
            $bank = Bank::find($payment->provider_id);
            if (!$bank) {
                throw new \Exception("Banco com ID {$payment->provider_id} não encontrado para o Payment {$this->paymentId}.");
            }

            // 2. Resolve o serviço da liquidante
            $acquirerService = $acquirerResolver->resolveByBank($bank);

            // 3. Prepara os dados para a API da liquidante
            //    (Usando o exemplo do E2Service que fizemos)
            $apiData = [
                'amount' => $payment->amount,
                'name' => $this->requestData['name'] ?? 'Cobrança PIX',
                'document' => $this->requestData['document'] ?? null,
                'description' => $this->requestData['description'] ?? " ",
                'identification' => $this->requestData['identification'] ?? " ",
                'externalId' => $payment->external_payment_id,
            ];

            // 4. Chama o método do serviço da liquidante para criar a cobrança
            //    Assumimos que o método se chama 'createPayment'
            if (!method_exists($acquirerService, 'createCharge')) {
                throw new \Exception("O método 'createCharge' não existe no serviço " . get_class($acquirerService));
            }
            $token = $acquirerService->getToken();
            $response = $acquirerService->createCharge($apiData, $token);


            // 5. Trata a resposta da API
            //    Esta parte pode precisar de adaptação dependendo da estrutura da resposta
            if (isset($response['statusCode']) && $response['statusCode'] < 400 && isset($response['data'])) {
                // Sucesso! Atualiza o pagamento com os dados recebidos
                $payment->update([
                    'provider_transaction_id' => $response['data']['uuid'] ?? null, // Ou o ID correto
                    'status' => 'pending', // Novo status
                    'provider_response_data' => $response['data'],
                ]);
                Log::info("✅ ProcessPayInJob: Cobrança criada com sucesso para Payment ID: {$this->paymentId}. Status: pending");
                SendOutgoingWebhookJob::dispatch($this->paymentId);
            } else {
                // Falha na API
                throw new \Exception('A API da adquirente retornou um erro: ' . json_encode($response));
            }
        } catch (Throwable $e) { // Apanha Exceptions e Errors
            // 6. Trata a falha (seja da API ou de lógica interna)
            Log::error("❌ Erro no ProcessPayInJob para Payment ID {$this->paymentId}: " . $e->getMessage());
            $payment->update([
                'status' => 'failed',
                'provider_response_data' => ['error' => $e->getMessage()] // Guarda o erro
            ]);
            // (Opcional) Você pode querer notificar alguém sobre a falha aqui
        }
    }
}
