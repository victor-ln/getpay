<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\{Account, Payment, Webhook, Bank, User, Balance, WebhookRequest, WebhookResponse}; // NOVO: Adicionado Balance
use App\Services\AcquirerResolverService;
use App\Services\FeeCalculatorService;
use App\Services\FeeService;
use App\Services\PlatformTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // NOVO: Importado para usar transações
use App\Traits\ToastTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;

class DubaiWebhookController extends Controller
{

    use ToastTrait;



    protected $feeService;
    protected $platformTransactionService;
    protected $acquirerResolver;
    protected $feeCalculatorService;

    public function __construct(
        FeeService $feeService,
        PlatformTransactionService $platformTransactionService,
        AcquirerResolverService $acquirerResolverService,
        FeeCalculatorService $feeCalculatorService
    ) {
        $this->feeService = $feeService;
        $this->platformTransactionService = $platformTransactionService;
        $this->acquirerResolver = $acquirerResolverService;
        $this->feeCalculatorService = $feeCalculatorService;
    }
    /**
     * Handle incoming webhooks from the Dubai acquirer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Webhook com JSON inválido recebido.');
            return response()->json(["error" => "Invalid JSON payload."], 400);
        }





        // 2. Registra a requisição do webhook imediatamente para auditoria
        $webhookRequest = WebhookRequest::create([
            "ip_address" => $request->ip(),
            "payload"    => $request->getContent(),
            "signature"  => $request->header("x-signature"),
        ]);


        if ($payload['data']['status'] == 'FAILED') {

            $payment = Payment::where('external_payment_id', $payload['data']['externalId'])->first();

            if (empty($payment)) {
                return response()->json(['message' => 'Pay-out não encontrado.'], 200);
            }

            if ($payment->status == 'paid') {
                return response()->json(['message' => 'Pay-out com status pago.'], 200);
            }


            $balance = Balance::firstOrCreate(
                ['account_id' => $payment->account_id],
                ['available_balance' => 0, 'blocked_balance' => 0]
            );





            $totalBlockedAmount = $payment->amount + $payment->fee;



            $payment->status = 'cancelled';
            $balance->blocked_balance -= $totalBlockedAmount; // Remove do bloqueado
            $balance->available_balance += $totalBlockedAmount; // E devolve para o disponível

            Log::info("Pay-out confirmado como 'CANCELED'. Saldo devolvido para disponível.", [
                'payment_id' => $payment->id,
                'amount_returned_to_available' => $totalBlockedAmount
            ]);

            $payment->save();
            $balance->save();


            $this->sendOutgoingWebhook($payment->account_id, $payment,  $payload);

            return response()->json(['message' => 'Pay-out confirmado como "CANCELED". Saldo devolvido para disponível, conta: ' . $payment->account_id], 200);
        }

        // 3. Validação do Payload
        $payload = $request->all();
        if (empty($payload['uuid']) || empty($payload['status'])) {
            Log::error('Payload de webhook inválido da Dubai: Faltando uuid ou status.', $payload);
            return response()->json(['error' => 'Invalid payload.'], 400);
        }

        // 4. Encontrar a Transação
        $payment = Payment::where('external_payment_id', $payload['transaction']['externalId'])->first();


        if (!$payment) {
            Log::warning('Webhook recebido para uma transação não encontrada no sistema.', ['uuid' => $payload['uuid']]);
            // Retornamos 200 OK para que a adquirente não tente reenviar um webhook para uma transação que não conhecemos.
            return response()->json(['status' => 'ok']);
        }

        // 5. Verificar se a transação já foi processada para evitar duplicidade
        if (in_array($payment->status, ['paid', 'cancelled'])) {
            Log::info('Webhook recebido para transação que já possui um status final.', [
                'payment_id' => $payment->id,
                'status_atual' => $payment->status
            ]);
            return response()->json(['message' => ' Webhook already processed.'], 200);
        }

        try {


            $transactionTypeInOurSystem = $payment->type_transaction;



            // 5. Direciona para o handler correto baseado no nosso banco de dados.
            switch ($transactionTypeInOurSystem) {
                case 'IN':
                    $this->handlePayinConfirmation($payment, $payload);
                    break;
                case 'OUT':
                    $this->handlePayoutConfirmation($payment, $payload);
                    break;
                default:
                    Log::warning("Tipo de transação desconhecido no nosso sistema.", ['payment_id' => $payment->id, 'type' => $transactionTypeInOurSystem]);
                    break;
            }

            // 7. Retornar Resposta 200 OK
            return response()->json(['status' => 'received']);
        } catch (\Exception $e) {
            Log::critical('Erro CRÍTICO ao processar webhook da Dubai.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            // Retorna um erro 500, o que pode fazer a adquirente tentar reenviar o webhook.
            return response()->json(['error' => 'Internal server error.'], 500);
        }
    }

    private function handlePayinConfirmation(Payment $payment, array $payload)
    {



        // Se o pagamento já foi processado, não faz nada para evitar duplicidade.
        if ($payment->status !== 'pending') {
            Log::info("Webhook de Pay-in recebido para pagamento que não está pendente.", ['payment_id' => $payment->id]);
            return;
        }







        DB::transaction(function () use ($payment, $payload) {
            $balance = Balance::firstOrCreate(
                ['account_id' => $payment->account_id],
                ['available_balance' => 0, 'blocked_balance' => 0]
            );
            Log::info('Saldo atual da conta ' . $payment->account_id . ': Disponível=' . $balance->available_balance . ', Bloqueado=' . $balance->blocked_balance);
            //$feeData = $this->feeService->calculateTransactionFee($payment->user, $payment->amount, 'IN');

            $account = Account::where('id', $payment->account_id)->first();
            $fee = $this->feeCalculatorService->calculate($account, $payment->amount, 'IN');




            $netAmount = $payment->amount - $fee;

            $cost = $this->feeService->calculateTransactionCost($payment->provider()->first(), 'IN', $payment->amount);


            $payment->status = 'paid';
            $payment->fee = $fee;
            $payment->cost = $cost;
            $payment->end_to_end_id = $payload['bankData']['endToEndId'] ?? '---';
            $payment->provider_response_data = $payload;
            $payment->name = $payload['bankData']['name'] ?? '---';
            $payment->document = $payload['documentNumber']['payerDocument'] ?? '---';
            $payment->platform_profit = (float) ($fee - $cost);
            $payment->save();

            $balance->available_balance += $netAmount;
            $balance->save();



            $this->platformTransactionService->creditProfitForTransaction($payment);

            Log::info("Pay-in confirmado. Saldo atualizado.", ['payment_id' => $payment->id, 'user_id' => $payment->user_id]);
        });

        $this->sendOutgoingWebhook($payment->account_id, $payment,  $payload);
    }

    /**
     * Lida com a confirmação de um PAY-OUT (Saque).
     */
    private function handlePayoutConfirmation(Payment $payment, array $payload)
    {



        // Se o pagamento não estava em 'processing', ignora.
        if ($payment->status !== 'processing') {
            Log::info("Webhook de Pay-out recebido para pagamento que não está em processamento.", ['payment_id' => $payment->id]);
            return;
        }

        $user = \App\Models\User::find($payment->user_id);
        $account = $user->accounts()->first();



        // Verificamos se o status é um dos que sabemos tratar (FINISHED ou CANCELED)
        if (!in_array($payload['status'], ['AWAITING', 'COMPLETED'])) {
            Log::info("Webhook de Pay-Out recebido com status não tratado pela adquirente.", [
                'payment_id' => $payment->id,
                'status_recebido' => $payload['status']
            ]);
            return;
        }


        DB::transaction(function () use ($payment, $payload) {
            $balance = Balance::firstOrCreate(
                ['account_id' => $payment->account_id],
                ['available_balance' => 0, 'blocked_balance' => 0]
            );
            Log::info('Saldo atual da conta ' . $payment->account_id . ': Disponível=' . $balance->available_balance . ', Bloqueado=' . $balance->blocked_balance);


            $totalBlockedAmount = $payment->amount + $payment->fee;


            // Usamos um switch para tratar cada status
            switch ($payload['status']) {

                case 'COMPLETED':
                    // LÓGICA EXISTENTE PARA SAQUE BEM-SUCEDIDO

                    $payment->status = 'paid';
                    $payment->name = $payload['bankData']['name'] ?? '---';
                    $payment->document = $payload['bankData']['documentNumber'] ?? '---';
                    $payment->provider_transaction_id = $payload['transaction']['uuid'] ?? '---';
                    $balance->blocked_balance -= $totalBlockedAmount; // Apenas remove do bloqueado

                    Log::info("Pay-out confirmado como 'FINISHED'. Saldo bloqueado liberado.", [
                        'payment_id' => $payment->id,
                        'amount_released_from_blocked' => $totalBlockedAmount
                    ]);
                    break;

                case 'AWAITING':
                    // NOVA LÓGICA PARA SAQUE CANCELADO

                    $payment->provider_transaction_id = $payload['transaction']['uuid'] ?? '---';


                    break;
            }

            $cost = $this->feeService->calculateTransactionCost($payment->provider()->first(), 'OUT', $payment->amount);
            $account = Account::where('id', $payment->account_id)->first();
            $fee = $this->feeCalculatorService->calculate($account, $payment->amount, 'OUT');


            $payment->fee = $fee;
            $payment->cost = $cost;
            $payment->end_to_end_id = $payload['bankData']['endtoendId'] ?? '---';
            $payment->provider_response_data = $payload;

            $payment->save();
            $balance->save();



            Log::info("Pay-out confirmado. Saldo bloqueado liberado.", [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'amount_released' => $totalBlockedAmount
            ]);

            if ($payload['status'] === 'COMPLETED') {
                $this->platformTransactionService->creditProfitForTransaction($payment);
            }
            // event(new \App\Events\PaymentConfirmed($payment)); // Futura notificação em tempo real
        });

        $this->sendOutgoingWebhook($payment->account_id, $payment,  $payload);
        return;
    }



    private function sendOutgoingWebhook(int $accountId, Payment $payment, $providerResponse): void
    {


        $responseData = $providerResponse;
        Log::info("DEBUG: Iniciando sendOutgoingWebhook para account_id: {$accountId}");

        try {
            // 1. Buscar configuração do webhook do cliente
            $webhookConfig = Webhook::where('account_id', $accountId)
                ->where(function ($query) use ($payment) {
                    // Procura pela configuração do tipo de transação (IN ou OUT)
                    $query->where('event', $payment->type_transaction)
                        // Ou por uma configuração genérica para todos os eventos
                        ->orWhere('event', 'ALL');
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();



            if (!$webhookConfig || empty($webhookConfig->url) || empty($webhookConfig->secret_token)) {
                // LOG IMPORTANTE: Se o código parar aqui, o problema é a configuração no banco.
                Log::warning("DEBUG: Nenhuma configuração de webhook de SAÍDA ativa foi encontrada para account_id: {$accountId} e evento: {$payment->type_transaction}. Abortando envio.");
                return;
            }

            Log::info("DEBUG: Configuração de webhook encontrada. URL: {$webhookConfig->url}");

            $urlClient = $webhookConfig->url;
            $secretKeyClient = $webhookConfig->secret_token;


            // 2. Construir o payload
            $payloadData = [
                'type' => $this->getWebhookType($payment),
                'externalId' => $payment->external_payment_id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'fee_applied' => $payment->fee,
                'endToEndId' => $responseData['bankData']['endtoendId'] ?? null,
                'processed_at' => now()->toIso8601String(),
                'uuid' => $payment->provider_transaction_id,

            ];

            if ($payment->status === 'paid' && $payment->type_transaction === 'IN') {

                $payloadData['metadata'] = [
                    'authCode' => $responseData['uuid'] ?? null,
                    'amount' => $payment->amount,
                    'paymentDateTime' => now()->toIso8601String(),
                    'pixKey' => $responseData['metadata']['pixKey'] ?? null,
                    'receiverName' => ' --- ',
                    'receiverBankName' => ' --- ',
                    'receiverDocument' => ' --- ',
                    'receiveAgency' => ' --- ',
                    'receiveAccount' => ' --- ',
                    'payerName' => $responseData['bankData']['name'] ?? null,
                    'payerAgency' => $responseData['bankData']['account'] ?? null,
                    'payerAccount' => $responseData['bankData']['account'] ?? null,
                    'payerDocument' => $responseData['bankData']['documentNumber'] ?? null,
                    'createdAt' => $payment->created_at->toIso8601String(),
                    'endToEnd' => $responseData['bankData']['endtoendId'] ?? null,
                ];
            } elseif ($payment->status === 'cancelled' && $payment->type_transaction === 'OUT') {

                $payloadData['reason_cancelled'] = $responseData['reason_cancelled'] ?? 'No reason provided.';
                $payloadData['metadata'] = []; // Envia metadata vazio, como solicitado

            } elseif ($payment->status === 'paid' && $payment->type_transaction === 'OUT') {

                $payloadData['metadata'] = [
                    'authCode' => $responseData['uuid'] ?? null,
                    'amount' => $payment->amount,
                    'paymentDateTime' => now()->toIso8601String(),
                    'pixKey' => $responseData['metadata']['key'] ?? null,
                    'receiverName' => $responseData['bankData']['name'] ?? ' --- ',
                    'receiverBankName' => $responseData['bankData']['ispb'] ?? ' --- ',
                    'receiverDocument' => $responseData['bankData']['documentNumber'] ?? ' --- ',
                    'receiveAgency' => $responseData['bankData']['account'] ?? ' --- ',
                    'receiveAccount' => $responseData['bankData']['account'] ?? ' --- ',
                    'payerName' => ' --- ',
                    'payerAgency' => ' --- ',
                    'payerAccount' => $responseData['transaction']['account'] ?? null,
                    'payerDocument' => $responseData['transaction']['account'] ?? null,
                    'createdAt' => $payment->created_at->toIso8601String(),
                    'endToEnd' => $responseData['bankData']['endtoendId'] ?? null,
                ];
            }

            $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $signature = hash_hmac('sha256', $jsonPayload, $secretKeyClient);

            // LOG IMPORTANTE: Verifique se os dados parecem corretos
            Log::debug("DEBUG: Preparando para enviar webhook.", [
                'url' => $urlClient,
                'payload' => $payloadData,
                'signature' => $signature
            ]);

            // 5. Enviar a requisição POST
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Signature' => $signature,
            ])
                ->withOptions([
                    'verify' => false
                ])

                ->timeout(15) // Adiciona um timeout de 15 segundos
                ->post($urlClient, $payloadData);

            // 6. Logar o resultado
            if ($response->successful()) {
                Log::info("Webhook de SAÍDA enviado com SUCESSO para account_id: {$accountId}. Status: {$response->status()}");
            } else {
                // LOG IMPORTANTE: Se cair aqui, a requisição foi feita, mas o servidor de destino respondeu com erro.
                Log::error("FALHA ao enviar webhook de SAÍDA para account_id: {$accountId}. Status: {$response->status()}", [
                    'url' => $urlClient,
                    'response_body' => $response->body()
                ]);

                WebhookResponse::create([
                    "webhook_request_id" => $webhookConfig->id,
                    "status_code" => $response->status(),
                    "headers" => json_encode($response->headers()), // Armazena cabeçalhos como JSON
                    "body" => json_encode($response->body()), // Armazena corpo como JSON
                ]);
            }
        } catch (\Exception $e) {
            // LOG IMPORTANTE: Se cair aqui, houve um erro de conexão ou outro erro crítico.
            Log::error("Erro CRÍTICO ao enviar webhook de SAÍDA para account_id: {$accountId}. Erro: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }


    private function getWebhookType($payment): string
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

        // Retorna um tipo padrão caso nenhuma condição seja atendida
        return 'UNKNOWN_EVENT';
    }

    private function verifyAuthHeaders(Request $request)
    {
        $user = $request->getUser();
        $password = $request->getPassword();

        // Pega as credenciais corretas que guardamos na nossa configuração
        $expectedUser = config('services.dubai.webhook_user');
        $expectedPassword = config('services.dubai.webhook_password');

        // Compara as credenciais
        if ($user !== $expectedUser || $password !== $expectedPassword) {
            // Se não baterem, lança uma exceção de autenticação, que será capturada no método handle
            throw new AuthenticationException('Invalid webhook credentials.');
        }

        // Se chegou até aqui, as credenciais são válidas.
    }
}
