<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PlatformAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Brick\Money\Money;

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

        // Calcula o lucro líquido a partir dos valores já 'casted' do model
        // Convertemos para Money para garantir a precisão na subtração
        $feeAmount = Money::of($payment->fee ?? 0, 'BRL');
        $costAmount = Money::of($payment->cost ?? 0, 'BRL');
        $netProfit = $feeAmount->minus($costAmount); // $netProfit é um objeto Money

        // Se não houve lucro (ou houve prejuízo), não credita nada.
        if ($netProfit->isLessThanOrEqualTo(Money::zero('BRL'))) {
            Log::info('Nenhum lucro a ser creditado para a transação.', ['payment_id' => $payment->id]);
            return;
        }

        try {
            DB::transaction(function () use ($payment, $netProfit) {
                $platformAccount = PlatformAccount::firstOrCreate(
                    ['bank_id' => $payment->provider_id],
                    ['account_name' =>  $payment->provider()->first()->name . ' Account']
                );

                // Trava a linha para atualização segura
                $platformAccount = PlatformAccount::lockForUpdate()->find($platformAccount->id);
                if(!$platformAccount) {
                    throw new \Exception("Could not lock PlatformAccount ID: {$platformAccount->id}");
                }

                // Converte o saldo atual para Money, soma o lucro líquido, e converte de volta para float
                $currentBalance = Money::of($platformAccount->current_balance, 'BRL');
                $newBalance = $currentBalance->plus($netProfit);

                // Atualiza o saldo com o valor float
                $platformAccount->current_balance = $newBalance->getAmount()->toFloat();
                $platformAccount->save();

                Log::info("Conta da plataforma creditada com lucro líquido.", [
                    'platform_account_id' => $platformAccount->id,
                    'bank_id' => $payment->provider_id,
                    'payment_id' => $payment->id,
                    'profit_credited' => $netProfit->getAmount()->toFloat(),
                    'new_platform_balance' => $newBalance->getAmount()->toFloat() // Loga o novo saldo
                ]);
            });
        } catch(\Exception $e) {
             Log::error("Erro ao creditar lucro na conta da plataforma.", [
                'payment_id' => $payment->id,
                'bank_id' => $payment->provider_id,
                'error' => $e->getMessage(),
            ]);
        }
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
