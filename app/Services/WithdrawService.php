<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Balance;
use App\Models\Bank;
use App\Models\Log as CustomLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\AcquirerResolverService;
use App\Services\FeeService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FA\Google2FA;
use App\Traits\ValidatesTwoFactorAuthentication;
use Illuminate\Support\Facades\Auth;

class WithdrawService
{
    protected $acquirerResolver;
    protected $feeService;
    protected $google2fa;
    protected $feeCalculatorService;
    use ValidatesTwoFactorAuthentication;

    // Ações de Log para consistência
    private const ACTION_WITHDRAW_INITIATED = 'WITHDRAW_INITIATED';
    private const ACTION_WITHDRAW_INSUFFICIENT_FUNDS = 'WITHDRAW_INSUFFICIENT_FUNDS';
    private const ACTION_WITHDRAW_FUNDS_BLOCKED = 'WITHDRAW_FUNDS_BLOCKED';
    private const ACTION_WITHDRAW_PROVIDER_SUCCESS = 'WITHDRAW_PROVIDER_SUCCESS';
    private const ACTION_WITHDRAW_PROVIDER_FAILURE = 'WITHDRAW_PROVIDER_FAILURE';
    private const ACTION_WITHDRAW_FUNDS_REVERSED = 'WITHDRAW_FUNDS_REVERSED';
    private const ACTION_WITHDRAW_DB_ERROR = 'WITHDRAW_DB_ERROR';

    /**
     * Injeção de Dependência dos services necessários.
     */
    public function __construct(
        AcquirerResolverService $acquirerResolverService,
        FeeService $feeService,
        Google2FA $google2fa,
        FeeCalculatorService $feeCalculatorService
    ) {
        $this->acquirerResolver = $acquirerResolverService;
        $this->feeService = $feeService;
        $this->google2fa = $google2fa;
        $this->feeCalculatorService = $feeCalculatorService;
    }

    /**
     * Processa a solicitação de saque de forma segura e transacional.
     *
     * @param \App\Models\User $user
     * @param array $data
     * @return array
     */
    public function processWithdrawal(User $user, array $data,  bool $skipTwoFactorCheck = false): array
    {


        $this->logAction($user, self::ACTION_WITHDRAW_INITIATED, ['request_data' => $data]);

        $traceId = \Illuminate\Support\Str::uuid()->toString();
        Log::info("[TRACE:{$traceId}] --- INÍCIO DO PROCESSAMENTO ---");
        $T1 = microtime(true); // Tempo inicial

        $data['pixKeyType'] = strtoupper($data['pixKeyType']);

        $validator = Validator::make($data, [
            'externalId'     => 'required|string|unique:payments,external_payment_id',
            'pixKey'         => 'required|string|max:255',
            'pixKeyType'     => 'required|string|max:255',
            'name'           => 'required|string|max:255',
            'documentNumber' => 'required|string|min:11',
            'amount'         => 'required|numeric|min:1.00',
            'tfa_code' => $skipTwoFactorCheck ? 'nullable|string' : 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()];
        }

        $validatedData = $validator->validated();



        // --- Bloco de Validação 2FA (Versão Final e à Prova de Falhas) ---
        // if (!$skipTwoFactorCheck && $user->two_factor_secret) {
        //     $this->verifyTwoFactorCode($user, $validatedData['tfa_code'], 'WITHDRAW');
        // }
        // --- FIM DO BLOCO 2FA ---


        $amountToWithdraw = (float) $validatedData['amount'];

        // 1. Calcula a taxa ANTES de tudo, usando o service injetado.
        // $feeData = $this->feeService->calculateTransactionFee($user, $amountToWithdraw, 'OUT');
        // $fee = (float) $feeData['applied_fee'];

        $account = $user->accounts()->first();
        $fee = $this->feeCalculatorService->calculate($account, $amountToWithdraw, 'OUT');





        // 2. Calcula o valor total a ser debitado da conta do usuário.
        $totalDebitAmount = $amountToWithdraw + $fee;




        // 3. Verifica se o saldo disponível cobre o débito total.
        if ($account->total_available_balance < $totalDebitAmount) {
            $this->logAction($user, self::ACTION_WITHDRAW_INSUFFICIENT_FUNDS, ['requested_total' => $totalDebitAmount, 'available' => $account->total_available_balance]);
            return ['success' => false, 'message' => 'Total available balance is insufficient to cover amount + fees.'];
        }

        // 2. Segunda verificação: O saldo no adquirente PADRÃO da conta é suficiente?
        //    Isso usa o outro método que criamos no model Account.
        $currentAcquirerBalance = $account->getCurrentAcquirerBalance();

        if (!$currentAcquirerBalance || $currentAcquirerBalance->available_balance < $totalDebitAmount) {
            $this->logAction($user, self::ACTION_WITHDRAW_INSUFFICIENT_FUNDS, ['requested_total' => $totalDebitAmount, 'available_on_current_acquirer' => $currentAcquirerBalance->available_balance ?? 0]);
            return ['success' => false, 'message' => 'Insufficient funds with the current default acquirer to perform this withdrawal.'];
        }

        $payment = null;
        $validatedData['account_id'] = $account->id ?? null;



        // 4. Bloqueia os fundos e cria o registro de pagamento de forma atômica.
        try {
            Log::info("[TRACE:{$traceId}] Salvando no banco de dados...");
            $T2 = microtime(true);

            DB::transaction(function () use ($user, $amountToWithdraw, $fee, $totalDebitAmount, $validatedData, &$payment) {

                $account = $user->accounts()->first();
                $balanceToUpdate = Balance::where('account_id', $account->id)
                    ->where('acquirer_id', $account->acquirer_id) // <-- A CONDIÇÃO CHAVE
                    ->lockForUpdate() // Trava a linha para evitar que outras requisições a alterem
                    ->firstOrFail();

                $balanceToUpdate->available_balance -= $totalDebitAmount;
                $balanceToUpdate->blocked_balance += $totalDebitAmount;
                $balanceToUpdate->save();

                $payment = $this->createPendingPayment($user, $validatedData, $fee);
            });
            $this->logAction($user, self::ACTION_WITHDRAW_FUNDS_BLOCKED, ['payment_id' => $payment->id, 'amount' => $totalDebitAmount]);
        } catch (Exception $e) {
            $this->logAction($user, self::ACTION_WITHDRAW_DB_ERROR, ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to lock funds.', 'error' => $e->getMessage()];
        }

        $T3 = microtime(true);
        Log::info("[TRACE:{$traceId}] Aguardando resposta do gateway... Duração para salvar no DB: " . round(($T3 - $T2) * 1000) . "ms");


        // 5. Se tudo correu bem até aqui, contata o gateway de pagamento externo.
        try {
            $response = $this->callAcquirer($validatedData);



            $payment->provider_transaction_id = $response['data']['uuid'] ?? null;
            $payment->save();

            $this->logAction($user, self::ACTION_WITHDRAW_PROVIDER_SUCCESS, ['payment_id' => $payment->id, 'provider_response' => $response]);
            return ['success' => true, 'message' => 'Withdrawal request sent successfully', 'data' => $response['data']];
        } catch (Exception $e) {
            // Se o gateway falhar, a transação é revertida.
            $account = $user->accounts()->first();
            $this->reverseBlockedFunds($account, $totalDebitAmount, $payment);
            $this->logAction($user, self::ACTION_WITHDRAW_PROVIDER_FAILURE, ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Could not process withdrawal with payment provider.', 'error' => $e->getMessage()];
        }
    }

    /**
     * Cria o registro de pagamento pendente.
     */
    protected function createPendingPayment(User $user, array $data, float $fee): Payment
    {


        //$activeBank = Bank::where('active', true)->first();
        $account = $user->accounts()->first();

        $activeBank = $account->acquirer;

        return Payment::create([
            'user_id' => $user->id,
            'account_id' => $account->id ?? null,
            'external_payment_id' => $data['externalId'],
            'amount' => $data['amount'],
            'fee' => $fee,
            'type_transaction' => 'OUT',
            'status' => 'processing',
            'provider_id' => $activeBank->id ?? null,
        ]);
    }

    /**
     * Reverte os fundos bloqueados em caso de falha externa.
     */
    protected function reverseBlockedFunds(Account $account, float $totalDebitAmount, Payment $payment = null)
    {
        $user = Auth::user();
        $this->logAction($user, self::ACTION_WITHDRAW_FUNDS_REVERSED, ['payment_id' => $payment->id ?? null, 'amount' => $totalDebitAmount]);

        DB::transaction(function () use ($account, $totalDebitAmount, $payment) {

            // [CORRIGIDO] Buscamos o saldo específico do adquirerente padrão da conta
            // para garantir que estamos revertendo o saldo da "carteira" correta.
            $balance = Balance::where('account_id', $account->id)
                ->where('acquirer_id', $account->acquirer_id) // <-- A CONDIÇÃO CHAVE
                ->lockForUpdate()
                ->first();

            // Se, por algum motivo, não encontrarmos um saldo para o adquirente atual,
            // é melhor parar para evitar inconsistências.
            if (!$balance) {
                // Logar um erro crítico aqui
                Log::error("Attempted to reverse funds for Account #{$account->id} but no balance record found for their default acquirer #{$account->acquirer_id}.");
                return;
            }

            // A lógica de reverter o saldo permanece a mesma,
            // mas agora temos certeza de que está sendo aplicada no registro certo.
            $balance->blocked_balance -= $totalDebitAmount;
            $balance->available_balance += $totalDebitAmount;
            $balance->save();

            if ($payment) {
                $payment->status = 'failed';
                $payment->save();
            }
        });
    }

    /**
     * Isola a chamada ao gateway de pagamento.
     */
    private function callAcquirer(array $validatedData): array
    {
        $user = Auth::user();
        $account = $user->accounts()->first();
        $acquirerService = $this->acquirerResolver->resolveAcquirerService($account);


        $token = $acquirerService->getToken();



        if (!$token) {
            throw new Exception('Failed to obtain authentication token from provider.');
        }

        $response = $acquirerService->createChargeWithdraw($validatedData, $token);





        if ($response['statusCode'] >= 400) {
            throw new Exception('Payment provider failed to process the request.');
        }

        return $response;
    }

    /**
     * Busca o saldo de forma eficiente na tabela 'balances'.
     */
    protected function getUserBalance(int $userId): ?Balance
    {
        return Balance::where('account_id', $userId)->first();
    }

    /**
     * Função de log genérica e reutilizável.
     */
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
