<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\Log as CustomLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\PayoutDestination;
use App\Services\AcquirerResolverService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayoutTakeService
{
    protected $acquirerResolver;

    // Ações de Log específicas
    private const ACTION_TAKE_PAYOUT_INITIATED = 'TAKE_PAYOUT_INITIATED';
    private const ACTION_TAKE_PAYOUT_SUCCESS = 'TAKE_PAYOUT_SUCCESS';
    private const ACTION_TAKE_PAYOUT_FAILURE = 'TAKE_PAYOUT_FAILURE';

    public function __construct(AcquirerResolverService $acquirerResolverService)
    {
        $this->acquirerResolver = $acquirerResolverService;
    }

    /**
     * Processa a retirada de lucro (Take) para um adquirente específico.
     *
     * @param Bank $sourceBank O banco de onde o dinheiro será sacado.
     * @param PayoutDestination $destination A chave PIX para onde o dinheiro será enviado.
     * @param float $amount O valor do lucro a ser sacado.
     * @return array
     */
    public function execute(Bank $sourceBank, PayoutDestination $destination, float $amount): array
    {
        $systemUser = User::find(1); // Utilizador do sistema
        $platformAccount = $systemUser->accounts()->first();

        $this->logAction($systemUser, self::ACTION_TAKE_PAYOUT_INITIATED, [
            'source_bank_id' => $sourceBank->id,
            'destination_id' => $destination->id,
            'amount' => $amount
        ]);

        // 1. Cria um registo de pagamento para auditoria com status 'processing'
        // Este é o único passo que mexe na base de dados neste momento.
        $payment = Payment::create([
            'user_id' => $systemUser->id,
            'account_id' => $platformAccount->id,
            'external_payment_id' => 'take_' . Str::uuid(),
            'amount' => $amount,
            'fee' => 0,
            'cost' => 0,
            'platform_profit' => 0,
            'type_transaction' => 'OUT',
            'status' => 'processing', // Ficará neste status até o webhook chegar
            'provider_id' => $sourceBank->id,
            'name' => $destination->owner_name,
            'document' => $destination->owner_document,
        ]);

        // 2. Tenta executar o saque na adquirente
        try {
            $acquirerData = [
                'externalId' => $payment->external_payment_id,
                'pixKey' => $destination->pix_key,
                'pixKeyType' => $destination->pix_key_type,
                'name' => $destination->owner_name,
                'documentNumber' => $destination->owner_document,
                'amount' => $amount,
            ];

            $acquirerService = $this->acquirerResolver->resolveByBank($sourceBank);
            $token = $acquirerService->getToken();
            $response = $acquirerService->createChargeWithdraw($acquirerData, $token);

            if ($response['statusCode'] >= 400) {
                throw new Exception('A adquirente falhou em processar o pedido: ' . json_encode($response));
            }

            // 3. Se teve sucesso, apenas atualiza o ID do provedor na transação
            $payment->update(['provider_transaction_id' => $response['data']['uuid'] ?? null]);

            $this->logAction($systemUser, self::ACTION_TAKE_PAYOUT_SUCCESS, ['payment_id' => $payment->id]);
            return ['success' => true, 'message' => 'Take payout request sent successfully', 'data' => $response['data']];
        } catch (Exception $e) {
            // 4. Se a chamada à API falhar, atualiza o status do pagamento para 'failed'
            $payment->update(['status' => 'failed']);
            $this->logAction($systemUser, self::ACTION_TAKE_PAYOUT_FAILURE, ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            // O saldo nunca foi tocado, então não há nada para reverter.
            return ['success' => false, 'message' => 'Could not process take payout with provider.', 'error' => $e->getMessage()];
        }
    }

    /**
     * Função de log genérica.
     */
    private function logAction(User $user, string $action, array $context = [])
    {
        try {
            CustomLog::create([
                'user_id' => $user->id,
                'action'  => $action,
                'data'    => json_encode($context),
                'ip'      => request()->ip() ?? 'system',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write to custom log table: ' . $e->getMessage());
        }
    }
}
