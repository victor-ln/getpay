<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PlatformAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlatformTransactionService
{
    /**
     * Credita o valor de uma taxa na conta da plataforma correspondente.
     * Chamado após um Pay-in ou Pay-out ser bem-sucedido.
     *
     * @param Payment $payment A transação que gerou a taxa.
     */
    public function creditProfitForTransaction(Payment $payment): void
    {
        if (!$payment->provider_id) {
            return;
        }

        // Calcula o lucro líquido
        $netProfit = $payment->fee - $payment->cost;

        if ($netProfit <= 0) {
            // Se não houve lucro (ou houve prejuízo), não credita nada.
            Log::info('Nenhum lucro a ser creditado para a transação.', ['payment_id' => $payment->id]);
            return;
        }

        DB::transaction(function () use ($payment, $netProfit) {
            $platformAccount = PlatformAccount::firstOrCreate(
                ['bank_id' => $payment->provider_id],
                ['account_name' =>  $payment->provider()->first()->name . ' Account']
            );

            $platformAccount->lockForUpdate();
            $platformAccount->increment('current_balance', $netProfit); // Incrementa com o lucro líquido

            Log::info("Conta da plataforma creditada com lucro líquido.", [
                'platform_account_id' => $platformAccount->id,
                'payment_id' => $payment->id,
                'profit_credited' => $netProfit
            ]);
        });
    }

    /**
     * Debita um valor de uma conta da plataforma para um Payout de sócio.
     * (Será usado pelo seu Job de Payout no futuro).
     *
     * @param int $bankId O ID do banco de onde o dinheiro sairá.
     * @param float $amount O valor a ser debitado.
     * @param string $reason Uma descrição para o log.
     * @return bool
     */
    public function debitForPartnerPayout(int $bankId, float $amount, string $reason): bool
    {
        // Esta lógica será usada pelo seu Job agendado
        // ...
        return true;
    }
}
