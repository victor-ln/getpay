<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Payment;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendOutgoingWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $paymentId;

    /**
     * Create a new job instance.
     * Recebe o ID do pagamento que foi atualizado.
     */
    public function __construct(int $paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("▶️ Iniciando SendOutgoingWebhookJob para Payment ID: {$this->paymentId}");

        $payment = Payment::find($this->paymentId);

        if (!$payment) {
            Log::error("❌ SendOutgoingWebhookJob: Payment ID {$this->paymentId} não encontrado.");
            return;
        }

        try {
            // 1. Buscar configuração do webhook do cliente
            $webhookConfig = Webhook::where('account_id', $payment->account_id)
                ->where(function ($query) use ($payment) {
                    // Procura pela configuração do tipo de transação (IN ou OUT)
                    $query->where('event', $payment->type_transaction)
                        // Ou por uma configuração genérica ('ALL')
                        ->orWhere('event', 'ALL');
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc') // Prioriza a mais recente se houver múltiplas
                ->first();

            if (!$webhookConfig || empty($webhookConfig->url) || empty($webhookConfig->secret_token)) {
                Log::warning("⚠️ SendOutgoingWebhookJob: Nenhuma configuração de webhook ativa encontrada para account_id: {$payment->account_id} e evento: {$payment->type_transaction}. Abortando envio.");
                return;
            }

            Log::debug("Configuração de webhook encontrada para Payment ID {$this->paymentId}. URL: {$webhookConfig->url}");

            // 2. Construir o payload
            $payloadData = $this->buildPayload($payment);
            $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // 3. Calcular a assinatura
            $signature = hash_hmac('sha256', $jsonPayload, $webhookConfig->secret_token);

            Log::debug("Preparando para enviar webhook para Payment ID {$this->paymentId}.", ['payload' => $payloadData]);

            // 4. Enviar a requisição POST
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Signature' => $signature, // Cabeçalho de assinatura
            ])
                ->withOptions([
                    'verify' => false, // Verifica o certificado SSL
                ])
                ->timeout(15) // Adiciona um timeout de 15 segundos
                ->withBody($jsonPayload, 'application/json')
                ->post($webhookConfig->url);

            // 5. Logar o resultado
            if ($response->successful()) {
                Log::info("✅ Webhook enviado com SUCESSO para Payment ID: {$this->paymentId}. Status da resposta do cliente: {$response->status()}");
                // (Opcional) Guardar a resposta do cliente, se necessário
                // WebhookResponse::create([...]);
            } else {
                Log::error("❌ FALHA ao enviar webhook para Payment ID: {$this->paymentId}. Status da resposta do cliente: {$response->status()}", [
                    'url' => $webhookConfig->url,
                    'response_body' => $response->body()
                ]);
                // (Opcional) Guardar a resposta de erro
                // WebhookResponse::create([...]);

                // Lança uma exceção para que a fila possa tentar reenviar o webhook mais tarde
                throw new \Exception("Client webhook endpoint returned status {$response->status()}");
            }
        } catch (\Throwable $e) { // Apanha Exceptions e Errors
            Log::error("❌ Erro CRÍTICO no SendOutgoingWebhookJob para Payment ID {$this->paymentId}: " . $e->getMessage());
            // Libera o job de volta para a fila com um delay para tentar novamente
            $this->release(60); // Tenta novamente em 60 segundos
        }
    }

    /**
     * Constrói o payload do webhook com base no estado do pagamento.
     */
    private function buildPayload(Payment $payment): array
    {
        $payload = [
            'type' => $this->getWebhookType($payment), // PAYIN_PROCESSING, PAYIN_CONFIRMED, PAYIN_FAILED, etc.
            'externalId' => $payment->external_payment_id,
            'uuid' => $payment->provider_transaction_id,
            'amount' => $payment->amount,
            'status' => $payment->status, // pending, waiting_payment, paid, failed
            'processed_at' => now()->toIso8601String(),
            'getpay_payment_id' => $payment->id, // ID interno da GetPay
            'provider_transaction_id' => $payment->provider_transaction_id, // ID da liquidante
            'pix' => $payment->provider_response_data['pix'] ?? null,
            'qrcode' => $payment->provider_response_data['pix'] ?? null,
        ];



        // Adiciona informações de erro se o pagamento falhou
        if ($payment->status === 'failed' && isset($payment->provider_response_data['error'])) {
            $payload['error_message'] = $payment->provider_response_data['error'];
        }

        return $payload;
    }

    /**
     * Determina o tipo do evento do webhook com base no estado do pagamento.
     */
    private function getWebhookType(Payment $payment): string
    {
        if ($payment->type_transaction === 'IN') {
            switch ($payment->status) {
                case 'paid':
                    return 'PAYIN_CONFIRMED';
                case 'failed':
                    return 'PAYIN_FAILED';
                case 'pending':
                    return 'PAYIN_PENDING'; // Ainda a ser processado pela GetPay
                default:
                    return 'PAYIN_UPDATED'; // Genérico
            }
        }
        // Adicione aqui a lógica para PAYOUT se este Job for reutilizado

        return 'UNKNOWN_EVENT';
    }
}
