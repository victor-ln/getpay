<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Webhook;
use App\Models\WebhookResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOutgoingWebhookPendingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas do job
     */
    public $tries = 3;

    /**
     * Timeout do job em segundos
     */
    public $timeout = 30;

    protected $payment;
    protected $transactionVerified;

    /**
     * Create a new job instance.
     */
    public function __construct(Payment $payment, $transactionVerified = null)
    {
        $this->payment = $payment;
        $this->transactionVerified = $transactionVerified;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accountId = $this->payment->account_id;

        // Extrai os dados da estrutura da liquidante
        $responseData = $this->transactionVerified['data'] ?? [];
        $pixData = $responseData['pix'][0] ?? null;

        Log::info("JOB: Iniciando SendOutgoingWebhookPendingJob para payment #{$this->payment->id}, account_id: {$accountId}", [
            'transaction_verified' => $this->transactionVerified
        ]);

        try {
            // 1. Buscar configuração do webhook do cliente
            $webhookConfig = Webhook::where('account_id', $accountId)
                ->where(function ($query) {
                    $query->where('event', $this->payment->type_transaction)
                        ->orWhere('event', 'ALL');
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$webhookConfig || empty($webhookConfig->url) || empty($webhookConfig->secret_token)) {
                Log::warning("JOB: Nenhuma configuração de webhook ativa encontrada para account_id: {$accountId} e evento: {$this->payment->type_transaction}");
                return;
            }

            Log::info("JOB: Configuração de webhook encontrada. URL: {$webhookConfig->url}");

            $urlClient = $webhookConfig->url;
            $secretKeyClient = $webhookConfig->secret_token;

            // 2. Construir o payload
            $payloadData = [
                'type' => $this->getWebhookType($this->payment),
                'externalId' => $this->payment->external_payment_id,
                'amount' => $this->payment->amount,
                'status' => $this->payment->status,
                'fee_applied' => $this->payment->fee,
                'endToEndId' => $pixData['endToEndId'] ?? null,
                'processed_at' => $pixData['horario'] ?? now()->toIso8601String(),
                'uuid' => $this->payment->provider_transaction_id,
            ];

            if ($this->payment->status === 'paid') {
                $payloadData['metadata'] = [
                    'authCode' => $responseData['txid'] ?? null,
                    'amount' => $this->payment->amount,
                    'paymentDateTime' => $this->payment->updated_at->toIso8601String(),
                    'pixKey' => $responseData['chave'] ?? null,
                    'receiveName' => $pixData['pagador']['nome'] ?? null,
                    'receiverBankName' => null, // Não disponível na resposta
                    'receiverDocument' => $pixData['pagador']['cpf'] ?? null,
                    'receiveAgency' => null, // Não disponível na resposta
                    'receiveAccount' => null, // Não disponível na resposta
                    'payerName' => $pixData['pagador']['nome'] ?? null,
                    'payerAgency' => null, // Não disponível na resposta
                    'payerAccount' => null, // Não disponível na resposta
                    'payerDocument' => $pixData['pagador']['cpf'] ?? null,
                    'createdAt' => $this->payment->created_at->toIso8601String(),
                    'endToEnd' => $pixData['endToEndId'] ?? null,
                    'txid' => $responseData['txid'] ?? null,
                    'horario' => $pixData['horario'] ?? null,
                ];
            } elseif ($this->payment->status === 'cancelled' && $this->payment->type_transaction === 'OUT') {
                $payloadData['reason_cancelled'] = $responseData['errorMessage'] ?? 'No reason provided.';
                $payloadData['metadata'] = [];
            }

            $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $signature = hash_hmac('sha256', $jsonPayload, $secretKeyClient);

            Log::debug("JOB: Preparando para enviar webhook.", [
                'payment_id' => $this->payment->id,
                'url' => $urlClient,
                'payload' => $jsonPayload,
                'signature' => $signature
            ]);

            // 3. Enviar a requisição POST
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Signature' => $signature,
            ])
                ->withOptions(['verify' => false])
                ->timeout(15)
                ->withBody($jsonPayload, 'application/json')
                ->post($urlClient);

            // 4. Logar e salvar o resultado
            if ($response->successful()) {
                Log::info("JOB: Webhook enviado com SUCESSO para payment #{$this->payment->id}, account_id: {$accountId}. Status: {$response->status()}");

                WebhookResponse::create([
                    "webhook_request_id" => $webhookConfig->id,
                    "status_code" => $response->status(),
                    "headers" => json_encode($response->headers()),
                    "body" => json_encode($response->body()),
                ]);
            } else {
                Log::error("JOB: FALHA ao enviar webhook para payment #{$this->payment->id}. Status: {$response->status()}", [
                    'url' => $urlClient,
                    'response_body' => $response->body()
                ]);

                WebhookResponse::create([
                    "webhook_request_id" => $webhookConfig->id,
                    "status_code" => $response->status(),
                    "headers" => json_encode($response->headers()),
                    "body" => json_encode($response->body()),
                ]);

                // Relança exceção para retry automático
                throw new \Exception("Webhook failed with status: {$response->status()}");
            }
        } catch (\Exception $e) {
            Log::error("JOB: Erro CRÍTICO ao enviar webhook para payment #{$this->payment->id}. Erro: " . $e->getMessage(), [
                'account_id' => $accountId,
                'trace' => $e->getTraceAsString()
            ]);

            // Salva o erro se houver configuração de webhook
            if (isset($webhookConfig)) {
                WebhookResponse::create([
                    "webhook_request_id" => $webhookConfig->id,
                    "status_code" => 0,
                    "headers" => json_encode(['error' => 'Exception occurred']),
                    "body" => json_encode(['error' => $e->getMessage()]),
                ]);
            }

            // Relança para que o job possa tentar novamente
            throw $e;
        }
    }

    /**
     * Função para determinar o tipo do evento do webhook.
     */
    private function getWebhookType(Payment $payment): string
    {
        if ($payment->type_transaction === 'IN' && $payment->status === 'paid') {
            return 'PAYIN_CONFIRMED';
        }

        if ($payment->type_transaction === 'OUT') {
            if ($payment->status === 'paid') {
                return 'PAYOUT_CONFIRMED';
            }
            if ($payment->status === 'cancelled') {
                return 'PAYOUT_CANCELED';
            }
        }

        return 'UNKNOWN_EVENT';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("JOB FAILED: SendOutgoingWebhookPendingJob falhou completamente após todas as tentativas para payment #{$this->payment->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
