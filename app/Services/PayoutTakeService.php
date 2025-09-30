<?php

namespace App\Services;

use App\Models\ActivityLog;
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


        try {

            $systemUser = User::find(1);

            if (!$systemUser) {
                Log::error('❌ System user não encontrado');
                throw new \Exception('System user not found');
            }


            $platformAccount = $systemUser->accounts()->first();

            if (!$platformAccount) {
                Log::error('❌ Platform account não encontrada');
                throw new \Exception('Platform account not found');
            }


            $this->logAction($systemUser, self::ACTION_TAKE_PAYOUT_INITIATED, [
                'source_bank_id' => $sourceBank->id,
                'destination_id' => $destination->id,
                'amount' => $amount
            ]);


            $payment = Payment::create([
                'user_id' => $systemUser->id,
                'account_id' => $platformAccount->id,
                'external_payment_id' => 'take_' . Str::uuid(),
                'amount' => $amount,
                'fee' => 0,
                'cost' => 0,
                'platform_profit' => 0,
                'type_transaction' => 'OUT',
                'status' => 'processing',
                'provider_id' => $sourceBank->id,
                'name' => $destination->owner_name,
                'document' => $destination->owner_document,
            ]);



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



            $statusCode = $response['statusCode'] ?? 500;



            if ($statusCode >= 400) {
                Log::error('❌ Status code indica erro', [
                    'status_code' => $statusCode,
                    'response' => $response
                ]);
                throw new \Exception('A adquirente falhou em processar o pedido: ' . json_encode($response));
            }


            $providerTransactionId = $response['data']['uuid'] ?? null;
            $payment->update(['provider_transaction_id' => $providerTransactionId]);


            $this->logAction($systemUser, self::ACTION_TAKE_PAYOUT_SUCCESS, ['payment_id' => $payment->id]);

            Log::info('✅ SUCESSO TOTAL');

            return [
                'success' => true,
                'message' => 'Take payout request sent successfully',
                'data' => $response['data']
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('❌ ERRO: RequestException capturada', [
                'payment_id' => $payment->id ?? 'não criado',
                'status_code' => $e->response->status(),
                'response_body' => $e->response->body(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            if (isset($payment)) {
                $payment->update(['status' => 'failed']);
            }

            $this->logAction($systemUser ?? User::find(1), self::ACTION_TAKE_PAYOUT_FAILURE, [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Could not process take payout with provider.',
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('❌ ERRO: Exception genérica capturada', [
                'payment_id' => $payment->id ?? 'não criado',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($payment)) {
                $payment->update(['status' => 'failed']);
            }

            $this->logAction($systemUser ?? User::find(1), self::ACTION_TAKE_PAYOUT_FAILURE, [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Could not process take payout with provider.',
                'error' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            Log::error('❌ ERRO CRÍTICO: Throwable capturado', [
                'payment_id' => $payment->id ?? 'não criado',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Critical error during payout execution.',
                'error' => $e->getMessage()
            ];
        }
    }
    /**
     * Função de log genérica.
     */
    private function logAction(User $user, string $action, array $context = [])
    {
        try {
            ActivityLog::create([
                'user_id' => $user->id,
                'action'  => $action,
                'context'    => json_encode($context),
                'ip'      => request()->ip() ?? 'system',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write to custom log table: ' . $e->getMessage());
        }
    }
}
