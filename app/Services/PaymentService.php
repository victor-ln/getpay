<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\Bank;
use App\Models\Payment;
use App\Models\Log as CustomLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\AcquirerResolverService;
use App\Traits\ValidatesTwoFactorAuthentication;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;

class PaymentService
{

    protected $acquirerResolver;
    use ValidatesTwoFactorAuthentication;
    protected $google2fa;

    private const ACTION_REFUND_INITIATED = 'REFUND_INITIATED';
    private const ACTION_REFUND_INSUFFICIENT_FUNDS = 'REFUND_INSUFFICIENT_FUNDS';
    private const ACTION_REFUND_FUNDS_BLOCKED = 'REFUND_FUNDS_BLOCKED';
    private const ACTION_REFUND_PROVIDER_SUCCESS = 'REFUND_PROVIDER_SUCCESS';
    private const ACTION_REFUND_PROVIDER_FAILURE = 'REFUND_PROVIDER_FAILURE';
    private const ACTION_REFUND_FUNDS_REVERSED = 'REFUND_FUNDS_REVERSED';
    private const ACTION_REFUND_DB_ERROR = 'REFUND_DB_ERROR';

    // Injeção de Dependência via construtor
    public function __construct(AcquirerResolverService $acquirerResolverService, Google2FA $google2fa)
    {
        $this->acquirerResolver = $acquirerResolverService;
        $this->google2fa = $google2fa;
    }


    /**
     * Process a payment through the selected acquirer
     */
    public function processPayment(array $data)
    {

        // Use um ID único para rastrear esta requisição específica nos logs
        $traceId = \Illuminate\Support\Str::uuid()->toString();
        Log::info("[TRACE:{$traceId}] --- INÍCIO DO PROCESSAMENTO ---");
        $T0 = microtime(true); // Tempo inicial

        $user = Auth::user();

        $isAdminOrPartner = in_array($user->level, ['admin', 'partner']);

        if ($isAdminOrPartner) {
            // Admin/Partner: sem verificação de mínimo
            $minAccount = 0; // ou qualquer valor padrão
            $account = $user->accounts()->first();
            $minAccount = $account->min_amount_transaction;
            $maxAccount = $account->max_amount_transaction;
        } else {
            // Usuário comum: deve ter conta associada
            $account = $user->accounts()->first();

            if (!$account) {
                abort(400, 'User must be associated with an account to perform this action');
            }

            $minAccount = $account->min_amount_transaction;
            $maxAccount = $account->max_amount_transaction;
        }



        // Garanta um piso mínimo absoluto para o sistema
        $effectiveMinAmount = max(0.01, (float) $minAccount);
        $maxAmount = max(0.01, (float) $maxAccount);




        // Validate input data
        $validator = Validator::make($data, [
            'externalId' => 'required|string|unique:payments,external_payment_id',
            'amount' => 'required|numeric|min:' . $effectiveMinAmount . '|max:' . $maxAmount,
            'document' => 'required',
            'name' => 'required|string',
            'identification' => 'nullable|string',
            'expire' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);





        if ($validator->fails()) {
            Log::error('Falha na validação ao criar pagamento.', [
                'errors' => $validator->errors()->all(), // Mostra todas as mensagens de erro
                'data_received' => $data                  // Mostra os dados que tentaram ser validados
            ]);
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];
        }

        $T1 = microtime(true);
        Log::info("[TRACE:{$traceId}] Validação concluída em: " . round(($T1 - $T0) * 1000) . "ms");



        $acquirerService = $this->acquirerResolver->resolveAcquirerService($account);







        // --- Medindo o GARGALO #1 ---
        Log::info("[TRACE:{$traceId}] Solicitando token da adquirente...");
        $T2 = microtime(true);



        // Get token
        $token = $acquirerService->getToken();



        $T3 = microtime(true);
        Log::info("[TRACE:{$traceId}] Token recebido. Duração da chamada do token: " . round(($T3 - $T2) * 1000) . "ms");




        if (!$token) {
            $this->logPaymentAttempt($data, [
                'statusCode' => 401,
                'data' => ['error' => 'Failed to obtain authentication token'],
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Authentication failed with the payment provider'
            ];
        }


        // --- Medindo o GARGALO #2 ---
        Log::info("[TRACE:{$traceId}] Criando cobrança na adquirente...");
        $T4 = microtime(true);

        // Create charge
        $response = $acquirerService->createCharge($data, $token);

        $T5 = microtime(true);
        Log::info("[TRACE:{$traceId}] Resposta da cobrança recebida. Duração da chamada de criação: " . round(($T5 - $T4) * 1000) . "ms");

        // --- Medindo o GARGALO #3 ---
        Log::info("[TRACE:{$traceId}] Salvando no banco de dados...");
        $T6 = microtime(true);



        // Log the attempt
        $save = $this->logPaymentAttempt($data, $response);



        // Process the response
        if ($response['statusCode'] === 200 || $response['statusCode'] === 201) {
            // Save payment to database
            $save =  $this->savePayment($data, $response);

            $T7 = microtime(true);
            Log::info("[TRACE:{$traceId}] Salvo no banco de dados. Duração: " . round(($T7 - $T6) * 1000) . "ms");

            $T_FINAL = microtime(true);
            Log::info("[TRACE:{$traceId}] --- FIM DO PROCESSAMENTO. Tempo total: " . round(($T_FINAL - $T0) * 1000) . "ms ---");



            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $response['data']
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment processing failed',
            'data' => $response['data']
        ];
    }


    public function verifyPayment(array $data)
    {
        $validator = Validator::make($data, [
            'uuid' => 'required|string',
        ]);



        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ];
        }

        $payment = Payment::where('provider_transaction_id', $data['uuid'])->first();

        $data['bank_id'] = $payment->provider_id;

        $user = \App\Models\User::find($payment->user_id);
        $account = $user->accounts()->first();



        $acquirerService = $this->acquirerResolver->resolveAcquirerService($account);

        // Get token
        $token = $acquirerService->getToken();


        if (!$token) {
            $this->logPaymentAttempt($data, [
                'statusCode' => 401,
                'data' => ['error' => 'Failed to obtain authentication token'],
                'data' => $data
            ]);

            return [
                'success' => false,
                'message' => 'Authentication failed with the payment provider'
            ];
        }

        $response = $acquirerService->verifyCharge($data['uuid'], $token);
        Log::create([
            'user_id' => $payment->user_id,
            'action' => 'Verify Payment',
            'data' => [
                'request' => $data,
                'response' => $response
            ],
            'ip' => request()->ip(),
        ]);

        return $response;
    }
    /**
     * Select the acquirer based on defined criteria
     */

    protected function selectAcquirer(array $data)
    {
        $activeBanks = Bank::where('active', true)->get();

        if ($activeBanks->isEmpty()) {
            throw new \Exception('No active acquirers found.');
        }

        $selectedBank = $activeBanks->first();

        $nameParts = explode(' ', $selectedBank->name);
        $firstName = strtolower($nameParts[0]);

        return $firstName;
    }

    /**
     * Save payment information to the database
     */
    protected function savePayment(array $data, array $response)
    {
        $userId = Auth::id();
        //   $activeBank = Bank::where('active', true)->first();
        $user = \App\Models\User::find($userId);
        $account = $user->accounts()->first();


        $activeBank = $account->acquirer;

        if (!$account) {
            // Isso indica um problema de dados. Um client deveria sempre ter uma conta.
            throw new \Exception("User {$user->name} is not associated with any account.");
        }

        // 3. Agora que temos a conta, podemos pegar o ID dela.
        $accountId = $account->id;





        $payment =  Payment::create([
            'account_id' => $accountId ?? null,
            'user_id' => $userId,
            'external_payment_id' => $data['externalId'],
            'amount' => $data['amount'],
            'fee' => 0,
            'type_transaction' => 'IN',
            'status' => 'pending',
            'document' => $data['document'],
            'provider_id' => $activeBank->id,
            'provider_transaction_id' => $response['data']['uuid'] ?? null
        ]);



        return $payment;
    }

    /**
     * Log payment attempt
     */
    protected function logPaymentAttempt(array $data, array $response)
    {
        $userId = Auth::id() ?? 1; // Default to 1 if not authenticated

        return CustomLog::create([
            'user_id' => $userId,
            'action' => 'createPayment',
            'data' => [
                'request' => $data,
                'response' => $response
            ],
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Processa a solicitação de reembolso de forma síncrona e segura.
     *
     * @param Payment $payment O pagamento original a ser reembolsado.
     * @return array
     */
    public function processRefund(Payment $payment, ?string $tfaCode): array
    {
        // 1. Validações internas
        if ($payment->type_transaction !== 'IN' || $payment->status === 'refunded' || $payment->status === 'refunding') {
            return ['success' => false, 'message' => 'Payment is not eligible for refund at this moment.'];
        }

        $user = $payment->user;
        if (!$user) {
            return ['success' => false, 'message' => 'User not found for this payment.'];
        }

        $this->logAction($user, 'REFUND_INITIATED', ['payment_id' => $payment->id]);





        try {
            // 2. Resolve o serviço da adquirente e faz a verificação prévia
            $acquirerService = $this->acquirerResolver->resolveFromPayment($payment);
            $token = $acquirerService->getToken();

            $verificationResponse = $acquirerService->verifyCharge($payment->provider_transaction_id, $token);

            if (($verificationResponse['statusCode'] ?? 500) >= 400) {
                throw new \Exception('Could not verify the charge status with the provider.');
            }


            // $this->verifyTwoFactorCode($user, $tfaCode, 'REFUND');

            // if ($tfaCode) {


            //     $isValid = false;

            //     // --- 1. Verificação do Código de Recuperação (com tratamento de erro) ---
            //     $recoveryCodes = []; // Inicia como um array vazio por segurança
            //     if ($user->two_factor_recovery_codes) {
            //         try {
            //             // Tenta descriptografar e decodificar.
            //             $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            //         } catch (\Exception $e) {
            //             // Se QUALQUER erro acontecer na descriptografia (unserialize, payload invalid, etc.),
            //             // nós registramos o erro, mas não quebramos a aplicação.
            //             Log::warning('Could not decrypt or decode recovery codes for user: ' . $user->id, [
            //                 'error' => $e->getMessage()
            //             ]);
            //             // A variável $recoveryCodes continua como um array vazio.
            //         }
            //     }

            //     $usedCodeIndex = null;
            //     // O 'foreach' agora funciona com segurança, pois $recoveryCodes é sempre um array.
            //     foreach (($recoveryCodes ?? []) as $index => $codeData) {
            //         if (is_array($codeData) && empty($codeData['used_at']) && hash_equals((string) $codeData['code'], $tfaCode)) {
            //             $usedCodeIndex = $index;
            //             break;
            //         }
            //     }

            //     if ($usedCodeIndex !== null) {
            //         // Se um código de recuperação válido foi encontrado, marca como usado e continua
            //         $recoveryCodes[$usedCodeIndex]['used_at'] = now()->toDateTimeString();
            //         $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
            //         $user->save();
            //         $isValid = true;
            //         $this->logAction($user, 'REFUND_2FA_RECOVERY_USED');
            //     } else {
            //         // --- 2. Se não, verifica o código normal do app (TOTP) ---
            //         try {
            //             $secret = decrypt($user->two_factor_secret);
            //             $isValid = $this->google2fa->verifyKey($secret, $tfaCode);
            //         } catch (\Exception $e) {
            //             Log::error('Could not decrypt 2FA secret for user: ' . $user->id, ['error' => $e->getMessage()]);
            //             $isValid = false;
            //         }
            //     }

            //     // --- 3. Verificação Final ---
            //     if (!$isValid) {
            //         $this->logAction($user, 'REFUND_2FA_FAILED');
            //         return ['success' => false, 'message' => 'The 2FA code is invalid.'];
            //     }

            //     $this->logAction($user, 'REFUND_2FA_SUCCESS');
            // }


            $providerData = $verificationResponse['data'];
            $providerStatus = $providerData['status'] ?? null;


            // 3. Trata o caso específico onde a transação JÁ foi revertida na adquirente
            if ($providerStatus === 'REVERSED') {
                // Sincroniza nosso banco de dados com a verdade (a adquirente)
                $payment->status = 'refunded'; // Usando um status final claro
                $payment->end_to_end_id = $providerData['endToEndId'] ?? $payment->end_to_end_id;
                $payment->provider_response_data = $providerData;
                $payment->save();

                $this->logAction($user, 'REFUND_STATUS_SYNCED', ['message' => 'Status was already REVERSED on provider.']);

                // Retorna uma mensagem clara e específica para o usuário
                return ['success' => false, 'message' => 'This payment has already been refunded. Your records have been updated.'];
            }

            // 4. Se não foi revertida, verifica se está no único estado que permite um novo reembolso
            if ($providerStatus !== 'FINISHED') {
                throw new \Exception("The charge status on the provider is '{$providerStatus}', not 'FINISHED'. Refund is not possible.");
            }
            // (Verificação de valor omitida para brevidade, mas pode ser adicionada aqui)

        } catch (\Exception $e) {
            Log::error("Refund pre-check failed.", ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Pre-refund check failed: ' . $e->getMessage()];
        }

        // Se a verificação passou, continua com a lógica de débito e chamada de reembolso
        $amountToDebit = $payment->amount;

        $account = $user->accounts()->first();

        try {
            DB::transaction(function () use ($payment, $account, $amountToDebit) {
                $balance = Balance::where('account_id', $account->id)->lockForUpdate()->firstOrFail();
                $balance->available_balance -= $amountToDebit;
                $balance->save();
                $payment->status = 'refunding'; // Status temporário enquanto aguarda a resposta final
                $payment->save();
            });
        } catch (\Exception $e) {
            // ... (lógica de erro da transação)
        }

        try {
            // 5. Chama a adquirente para EXECUTAR o reembolso
            $refundData = ['provider_transaction_id' => $payment->provider_transaction_id];
            $response = $acquirerService->createChargeRefund($refundData, $token);

            if (($response['statusCode'] ?? 500) >= 400 || ($response['data']['status'] ?? '') !== 'REVERSED') {
                throw new \Exception('Provider failed to confirm the refund processing.');
            }

            // 6. Se a adquirente confirmou, atualiza nosso status final
            $payment->status = 'refunded';
            $payment->save();

            // (Opcional) Cria um novo registro de transação do tipo REFUND_OUT para o extrato

            return ['success' => true, 'message' => 'Refund processed successfully.'];
        } catch (\Exception $e) {
            // Lógica crítica para reverter o débito do merchant se a chamada final falhar
            // ... (log e possível job para reverter)
            return ['success' => false, 'message' => 'An error occurred while communicating with the payment provider.'];
        }
    }

    private function logAction(User $user, string $action, array $context = [])
    {
        try {
            CustomLog::create([
                'user_id' => $user->id,
                'action'  => $action,
                'data'    => json_encode($context),
                'ip'      => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write to custom log table: ' . $e->getMessage());
        }
    }
}
