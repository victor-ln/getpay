<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\{Account, Payment, Webhook, Bank, User, Balance, WebhookRequest, WebhookResponse, BalanceHistory}; // NOVO: Adicionado Balance
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

class OwenWebhookController extends Controller
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

    public function handleWebhook(Request $request)
    {
        // 1. Decodifica o payload e lida com JSON inválido
        $payload = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Webhook com JSON inválido recebido.');
            return response()->json(["error" => "Invalid JSON payload."], 400);
        }





        // 2. Registra a requisição do webhook imediatamente para auditoria
        $webhookRequest = WebhookRequest::create([
            "ip_address" => $request->ip(),
            "payload"    => $request->getContent(),
            "signature"  => $request->header("Authorization"),
        ]);


        if (!$this->verifySignature($request->getContent(), $request->header("Authorization"))) {
            Log::warning('Webhook recebido com assinatura inválida.', ['ip' => $request->ip()]);
            return response()->json(["error" => "Invalid signature."], 403);
        }





        try {
            // Pega o ID da transação vindo do gateway. Note que no seu payload é só 'id'.
            $providerTransactionId = $payload['object']['entryId'] ??  $payload['object']['metadata']['idempotencyKey'] ?? null;




            if (!$providerTransactionId) {
                throw new Exception("Payload do webhook não contém o campo 'id'.");
            }

            // 3. Encontra o pagamento correspondente no nosso sistema
            // $payment = Payment::where('provider_transaction_id', $providerTransactionId)->first();
            $payment = Payment::with('provider')->where('provider_transaction_id', $providerTransactionId)->first();




            if (!$payment) {
                Log::warning("Webhook recebido para uma transação não encontrada.", ['provider_id' => $providerTransactionId]);
                return response()->json(["message" => "Transaction not found, but webhook acknowledged."]);
            }

            // Associa o User ID ao log do webhook que criamos
            $webhookRequest->user_id = $payment->user_id;
            $webhookRequest->save();


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

            // 6. Envia o webhook de saída para o cliente, se necessário
            //$this->sendOutgoingWebhook($payment->account_id, $payment, $payload);

            return response()->json(["message" => "Webhook processado com sucesso."]);
        } catch (Exception $e) {
            Log::error("Erro ao processar webhook: " . $e->getMessage(), ["request_id" => $webhookRequest->id, "exception" => $e]);
            return response()->json(["error" => "Erro interno ao processar webhook."], 500);
        }
    }

    /**
     * Lida com a confirmação de um PAY-IN (Depósito).
     */
    private function handlePayinConfirmation(Payment $payment, array $payload)
    {



        // Se o pagamento já foi processado, não faz nada para evitar duplicidade.
        if ($payment->status !== 'pending') {
            Log::info("Webhook de Pay-in recebido para pagamento que não está pendente.", ['payment_id' => $payment->id]);
            return;
        }


        if ($payload['event'] != 'pix_in:qrcode_paid') {
            Log::info("Webhook de Pay-in recebido mas com evento inválido.", ['payment_id' => $payment->id]);
            return;
        }






        DB::transaction(function () use ($payment, $payload) {
            $balance = Balance::firstOrCreate(
                [
                    'account_id'  => $payment->account_id,
                    'acquirer_id' => $payment->provider_id,
                ],
                [
                    'available_balance' => 0,
                    'blocked_balance'   => 0,
                ]
            );
            Log::info('Saldo atual da conta ' . $payment->account_id . ': Disponível=' . $balance->available_balance . ', Bloqueado=' . $balance->blocked_balance);
            //$feeData = $this->feeService->calculateTransactionFee($payment->user, $payment->amount, 'IN');

            $account = Account::where('id', $payment->account_id)->first();
            $fee = $this->feeCalculatorService->calculate($account, $payment->amount, 'IN');

            $balanceBefore = $balance->available_balance;




            $netAmount = $payment->amount - $fee;

            $cost = $this->feeService->calculateTransactionCost($payment->provider()->first(), 'IN', $payment->amount);
            $payment->provider_response_data = $payload;

            switch ($payload['object']['status']) {

                case 'succeeded':
                    $payment->status = 'paid';
                    $payment->fee = $fee;
                    $payment->cost = $cost;
                    $payment->end_to_end_id = $payload['object']['endToEndId'] ?? '---';
                    $payment->provider_response_data = $payload;
                    $payment->name = $payload['object']['payer']['name'] ?? '---';
                    $payment->document = $payload['object']['payer']['cpfCnpj'] ?? '---';
                    $payment->platform_profit = (float) ($fee - $cost);


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
                        'description' => 'PIX deposit received: ' . $payment->amount . ' | Fee applied: ' . $fee . ' | id: ' . $payment->id,
                    ]);

                    break;

                case 'CANCEL':
                    $payment->status = 'cancelled';
                    $payment->provider_response_data = $payload;
                    //  $payment->save();
                    break;
            }



            $payment->save();






            $this->platformTransactionService->creditProfitForTransaction($payment);

            Log::info("Pay-in confirmado. Saldo atualizado.", ['payment_id' => $payment->id, 'user_id' => $payment->user_id]);
        });

        $this->sendOutgoingWebhook($payment->account_id, $payment,  $payload);
    }

    /**
     * Lida com a confirmação de um PAY-OUT (Saque).
     */
    private function handlePayoutConfirmation(Payment $payment, array $webhookData)
    {




        // Se o pagamento não estava em 'processing', ignora.
        if ($payment->status !== 'processing') {
            Log::info("Webhook de Pay-out recebido para pagamento que não está em processamento.", ['payment_id' => $payment->id]);
            return;
        }

        $user = \App\Models\User::find($payment->user_id);
        $account = $user->accounts()->first();

        //$acquirerService = $this->acquirerResolver->resolveAcquirerService($account);
        $bank = Bank::find($payment->provider_id);

        // 2. Chama o novo método para obter o serviço correto
        $acquirerService = $this->acquirerResolver->resolveByBank($bank);
        $token = $acquirerService->getToken();


        $transactionVerified = $acquirerService->verifyChargePayOut($webhookData['object']['endToEndId'], $token);



        $acquirerStatus = $transactionVerified['data']['status'] ?? null;



        // Verificamos se o status é um dos que sabemos tratar (FINISHED ou CANCELED)
        if (!in_array($acquirerStatus, ['succeeded', 'processing', 'failed'])) {
            Log::info("Webhook de Pay-Out recebido com status não tratado pela adquirente.", [
                'payment_id' => $payment->id,
                'status_recebido' => $acquirerStatus
            ]);
            return;
        }


        DB::transaction(function () use ($payment, $webhookData, $transactionVerified, $acquirerStatus) {
            $balance = Balance::firstOrCreate(
                [
                    'account_id'  => $payment->account_id,
                    'acquirer_id' => $payment->provider_id,
                ],
                [
                    'available_balance' => 0,
                    'blocked_balance'   => 0,
                ]
            );
            Log::info('Saldo atual da conta ' . $payment->account_id . ': Disponível=' . $balance->available_balance . ', Bloqueado=' . $balance->blocked_balance);


            $totalBlockedAmount = $payment->amount + $payment->fee;
            $balanceBefore = $balance->available_balance;
            $balanceAfter = $balanceBefore;


            // Usamos um switch para tratar cada status
            switch ($acquirerStatus) {

                case 'succeeded':
                    // LÓGICA EXISTENTE PARA SAQUE BEM-SUCEDIDO

                    $payment->status = 'paid';
                    $payment->name = $webhookData['object']['receiver']['name'] ?? '---';
                    $payment->document = $webhookData['object']['receiver']['cpfCnpj'] ?? '---';
                    $balance->blocked_balance -= $totalBlockedAmount; // Apenas remove do bloqueado

                    BalanceHistory::create([
                        'account_id' => $payment->account_id,
                        'acquirer_id' => $payment->provider_id,
                        'payment_id' => $payment->id,
                        'type' => 'debit',
                        'balance_before' => $balanceBefore,
                        'amount' => $totalBlockedAmount, // Positivo para indicar um crédito
                        'balance_after' => $balanceAfter,
                        'description' => 'Withdrawal finished. ID: ' . $payment->external_payment_id,
                    ]);


                    Log::info("Pay-out confirmado como 'FINISHED'. Saldo bloqueado liberado.", [
                        'payment_id' => $payment->id,
                        'amount_released_from_blocked' => $totalBlockedAmount
                    ]);
                    break;

                case 'failed':
                    // NOVA LÓGICA PARA SAQUE CANCELADO

                    $payment->status = 'cancelled';
                    $balance->blocked_balance -= $totalBlockedAmount; // Remove do bloqueado
                    $balance->available_balance += $totalBlockedAmount; // E devolve para o disponível

                    $balanceAfter = $balance->available_balance;
                    BalanceHistory::create([
                        'account_id' => $payment->account_id,
                        'acquirer_id' => $payment->provider_id,
                        'payment_id' => $payment->id,
                        'type' => 'credit',
                        'balance_before' => $balanceBefore,
                        'amount' => $totalBlockedAmount, // Positivo para indicar um crédito
                        'balance_after' => $balanceAfter,
                        'description' => 'Reversal for withdrawal ID: ' . $payment->external_payment_id,
                    ]);

                    Log::info("Pay-out confirmado como 'CANCELED'. Saldo devolvido para disponível.", [
                        'payment_id' => $payment->id,
                        'amount_returned_to_available' => $totalBlockedAmount
                    ]);
                    break;
            }

            $cost = $this->feeService->calculateTransactionCost($payment->provider()->first(), 'OUT', $payment->amount);
            $account = Account::where('id', $payment->account_id)->first();
            $fee = $this->feeCalculatorService->calculate($account, $payment->amount, 'OUT');


            $payment->fee = $fee;
            $payment->cost = $cost;
            $payment->end_to_end_id = $webhookData['object']['endToEndId'] ?? '---';
            $payment->provider_response_data = $webhookData;
            $payment->platform_profit = (float) ($fee - $cost);

            $payment->save();
            $balance->save();



            Log::info("Pay-out confirmado. Saldo bloqueado liberado.", [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'amount_released' => $totalBlockedAmount
            ]);

            if ($acquirerStatus === 'FINISHED') {
                $this->platformTransactionService->creditProfitForTransaction($payment);
            }
            // event(new \App\Events\PaymentConfirmed($payment)); // Futura notificação em tempo real
        });

        $this->sendOutgoingWebhook($payment->account_id, $payment,  $webhookData);
        return;
    }

    public function resendWebhook(Request $request)
    {

        $dados = $request->all();



        $startDate = $dados['start'] ?? ' ';
        $endDate =   $dados['end'] ?? ' ';
        $account = $dados['account'] ?? ' ';
        $type = $dados['type'] ?? ' ';
        $id = $dados['id'] ?? ' ';



        // if (!empty($id)) {
        //     $payments = Payment::where('provider_transaction_id', $id)
        //         ->get();

        //     $account = $payments->first()->account_id;
        // } else {
        //     $payments = Payment::where('account_id', $account)
        //         ->where('status', 'paid')
        //         ->where('type_transaction', $type)
        //         ->whereBetween('created_at', [$startDate, $endDate])
        //         ->orderBy('created_at', 'desc') // ou 'asc' para ordem crescente
        //         ->get();
        // }

        $payments = Payment::where('account_id', $account)
            ->where('status', 'paid')
            ->where('type_transaction', $type)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc') // ou 'asc' para ordem crescente
            ->get();



        Log::info("Iniciando reenvio de {$payments->count()} webhooks para a conta: {$account}");



        foreach ($payments as $payment) {


            try {

                //$transactionVerified = $payment->provider_response_data;
                $transactionVerified = json_decode($payment->provider_response_data, true);

                $this->sendOutgoingWebhook($payment->account_id, $payment, $transactionVerified);
            } catch (\Exception $e) {
                Log::error("Erro CRÍTICO ao reenviar webhook.", [
                    'payment_id' => $payment->id,
                    'account_id' => $payment->account_id,
                    'error_message' => $e->getMessage()
                ]);
            }
        }
    }




    private function sendOutgoingWebhook(int $accountId, Payment $payment, $providerResponse): void
    {


        $responseData = $providerResponse ?? [];
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
                'endToEndId' => $responseData['object']['endToEndId'] ?? null,
                'processed_at' => now()->toIso8601String(),
                'uuid' => $payment->provider_transaction_id,

            ];

            if ($payment->status === 'paid') {

                $payloadData['metadata'] = [
                    'authCode' => $responseData['object']['entryId'] ?? null,
                    'amount' => $payment->amount,
                    'paymentDateTime' => $responseData['object']['updatedAt'] ?? null,
                    'pixKey' => $responseData['object']['pixKey'] ?? null,
                    'receiveName' => $responseData['object']['receiver']['name'] ?? null,
                    'receiverBankName' => $responseData['object']['receiver']['ispb'] ?? null,
                    'receiverDocument' => $responseData['object']['receiver']['cpfCnpj'] ?? null,
                    'receiveAgency' => $responseData['object']['receiver']['agency'] ?? null,
                    'receiveAccount' => $responseData['object']['receiver']['accountNumber'] ?? null,
                    'payerName' => $responseData['object']['payer']['name'] ?? null,
                    'payerAgency' => $responseData['object']['payer']['agency'] ?? null,
                    'payerAccount' => $responseData['object']['payer']['accountNumber'] ?? null,
                    'payerDocument' => $responseData['object']['payer']['cpfCnpj'] ?? null,
                    'createdAt' => $payment->created_at->toIso8601String(),
                    'endToEnd' => $responseData['object']['endToEndId'] ?? null,
                ];
            } elseif ($payment->status === 'cancelled' && $payment->type_transaction === 'OUT') {
                $payloadData['reason_cancelled'] = $responseData['reason_cancelled'] ?? 'No reason provided.';
                $payloadData['metadata'] = []; // Envia metadata vazio, como solicitado
            }

            $jsonPayload = json_encode($payloadData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $signature = hash_hmac('sha256', $jsonPayload, $secretKeyClient);

            // LOG IMPORTANTE: Verifique se os dados parecem corretos
            Log::debug("DEBUG: Preparando para enviar webhook.", [
                'url' => $urlClient,
                'payload' => $jsonPayload,
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
                ->withBody($jsonPayload, 'application/json')
                ->post($urlClient);

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

            WebhookResponse::create([
                "webhook_request_id" => $webhookConfig->id,
                "status_code" => $response->status(),
                "headers" => json_encode($response->headers()), // Armazena cabeçalhos como JSON
                "body" => json_encode($response->body()), // Armazena corpo como JSON
            ]);
        }
    }

    /**
     * Função "tradutora" privada para determinar o tipo do evento do webhook.
     *
     * @param \App\Models\Payment $payment
     * @return string
     */
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

    /**
     * Verifica a assinatura do webhook.
     *
     * @param string $payload
     * @param string|null $receivedSignature
     * @return bool
     */
    private function verifySignature(string $payload, ?string $receivedSignature): bool
    {
        // Obtenha a chave secreta do .env ou config
        $secretKey = "8f3e2a9d7c1b5f4e6d8a2c9b7f1e4d3c6a5b8f2e1d7c4b9f3e6a8d2c5b1f7e4d"; // Exemplo: config/services.php -> ["webhook" => ["secret" => env("WEBHOOK_SECRET")]]
        // Ou diretamente do env: $secretKey = env("WEBHOOK_SECRET");

        if (empty($secretKey)) {
            Log::error("Chave secreta do webhook não configurada.");
            return false; // Não pode validar sem a chave
        }

        if (empty($receivedSignature)) {
            Log::warning("Assinatura do webhook ausente no cabeçalho.");
            return false; // Não pode validar sem a assinatura recebida
        }

        if ($receivedSignature === $secretKey) {
            return true;
        }
        return false;
    }

    /**
     * Registra a resposta enviada para o webhook.
     *
     * @param WebhookRequest $webhookRequest
     * @param int $statusCode
     * @param array $body
     * @param array $headers
     */
    private function logResponse(WebhookRequest $webhookRequest, int $statusCode, array $body, array $headers = []): void
    {
        try {
            WebhookResponse::create([
                "webhook_request_id" => $webhookRequest->id,
                "status_code" => $statusCode,
                "headers" => json_encode($headers), // Armazena cabeçalhos como JSON
                "body" => json_encode($body), // Armazena corpo como JSON
            ]);
        } catch (\Exception $e) {
            Log::error("Falha ao registrar resposta do webhook: " . $e->getMessage(), [
                "request_id" => $webhookRequest->id,
                "status_code" => $statusCode
            ]);
        }
    }


    public function store(Request $request, Account $account)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:255',
            'event' => 'required|in:IN,OUT',
        ]);

        $webhook = $account->webhooks()->create([
            'user_id' => Auth::user()->id,
            'url' => $validated['url'],
            'event' => $validated['event'],
            'secret_token' => Str::random(64),
            'is_active' => true,
        ]);

        // Renderiza o HTML do novo webhook
        $html = view('_partials.webhook-item', compact('webhook'))->render();

        return response()->json([
            'success' => true,
            'message' => 'Webhook saved successfully!',
            'html' => $html
        ]);
    }

    public function regenerate(Account $account, Webhook $webhook)
    {

        $user = Auth::user();
        // Opcional: você pode verificar se o webhook pertence ao user
        if ($user->level !== 'admin' && $webhook->account_id !== $account->id) {
            abort(403, 'Webhook does not belong to this user');
        }

        $webhook->update([
            'secret_token' => Str::random(64),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Secret token regenerated successfully!',
            'new_token' => $webhook->secret_token
        ]);
    }

    public function destroy(Account $account, Webhook $webhook)
    {
        $user = Auth::user();
        // Opcional: você pode verificar se o webhook pertence ao user
        // if ($user->level !== 'admin' && $webhook->account_id !== $account->id) {
        //     abort(403, 'Webhook does not belong to this user');
        // }

        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully!'
        ]);
    }
}
