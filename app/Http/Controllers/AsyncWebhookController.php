<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Support\Facades\Log;
use Exception;

class AsyncWebhookController extends Controller
{
    /**
     * Ponto de entrada principal para os webhooks assíncronos (V2).
     * Este método é desenhado para ser o mais RÁPIDO possível.
     */
    public function handleWebhook(Request $request)
    {
        // 1. Decodifica o payload
        $payload = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AsyncWebhookController: JSON inválido recebido.');
            // Retorna erro, pois o payload é ilegível
            return response()->json(["error" => "Invalid JSON payload."], 400);
        }

        // Opcional: Registar o $webhookRequest aqui, se necessário para auditoria imediata
        // WebhookRequest::create([...]);

        try {
            // 2. Extrai o ID da transação da liquidante
            // (Baseado no seu XdpagWebhookController, o ID está em 'data' -> 'id')
            $providerTransactionId = $payload['data']['id'] ?? null;

            if (!$providerTransactionId) {
                // Se não conseguirmos um ID, não há nada para processar.
                Log::warning("AsyncWebhookController: Payload do webhook não contém 'data.id'.", ['payload' => $payload]);
                // Mesmo assim, respondemos 200 para a liquidante parar de enviar.
                return response()->json(["message" => "Webhook received but ID missing."]);
            }

            // 3. Encontra o pagamento correspondente no nosso sistema
            $payment = Payment::where('provider_transaction_id', $providerTransactionId)->first();

            if (!$payment) {
                Log::warning("AsyncWebhookController: Webhook recebido para transação não encontrada.", ['provider_id' => $providerTransactionId]);
                // Respondemos 200 OK para a liquidante não continuar a enviar.
                return response()->json(["message" => "Transaction not found, but webhook acknowledged."]);
            }

            // 4. [VERIFICAÇÃO DE SEGURANÇA] Se o pagamento já foi processado, ignora.
            if (!in_array($payment->status, ['pending', 'processing'])) {
                Log::info("AsyncWebhookController: Webhook para Payment #{$payment->id} já processado (Status: {$payment->status}). Ignorando.");
                // Respondemos 200 OK.
                return response()->json(["message" => "Webhook for already processed transaction. Ignored."]);
            }

            // 5. [A AÇÃO PRINCIPAL] Despacha o Job para a fila
            ProcessWebhookJob::dispatch($payment->id, $payload);

            Log::info("AsyncWebhookController: ProcessWebhookJob despachado para Payment ID: {$payment->id}.");

            // 6. Responde imediatamente à liquidante
            return response()->json(["message" => "Webhook received and queued for processing."]);
        } catch (Exception $e) {
            // Apanha qualquer erro inesperado (ex: falha ao despachar para a fila)
            Log::error("Erro grave no AsyncWebhookController: " . $e->getMessage(), ["exception" => $e]);
            // Retorna 500 para sinalizar que algo falhou internamente
            return response()->json(["error" => "Internal error while queueing webhook."], 500);
        }
    }
}
