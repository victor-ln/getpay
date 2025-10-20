<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FeeProfile;
use Illuminate\Support\Facades\Log;
use Brick\Money\Money;
use Brick\Math\RoundingMode;

class FeeCalculatorService
{
    /**
     * Calcula a taxa para uma determinada conta, valor e tipo de transação.
     *
     * @param Account $account A conta que está realizando a transação.
     * @param Money $transactionValue O valor da transação.
     * @param string $transactionType O tipo de transação ('IN' ou 'OUT').
     * @return Money A taxa calculada.
     */
    public function calculate(Account $account, Money $transactionValue, string $transactionType): Money
    {
        $transactionType = strtoupper($transactionType);

        // --- LÓGICA DE BUSCA CORRIGIDA ---

        // 1. Tenta encontrar uma regra ATIVA e específica para o tipo de transação (IN ou OUT).
        $profile = $account->feeProfiles()
            ->wherePivot('transaction_type', $transactionType)
            ->wherePivot('status', 'active') // <-- CORREÇÃO
            ->first();



        // 2. Se não encontrar, tenta encontrar uma regra ATIVA do tipo 'DEFAULT' para esta conta.
        if (!$profile) {
            $profile = $account->feeProfiles()
                ->wherePivot('transaction_type', 'DEFAULT')
                ->wherePivot('status', 'active') // <-- CORREÇÃO
                ->first();
        }

        // 3. Se ainda não houver um perfil, busca por um perfil global padrão.
        if (!$profile) {
            $profile = FeeProfile::where('name', 'Perfil Padrão Global')->first();
        }

        if (!$profile) {
            Log::warning('Nenhum perfil de taxa ativo foi encontrado para a conta: ' . $account->id);
            return Money::of(0, 'BRL');
        }

        // A partir daqui, a lógica do switch-case que já tínhamos funciona perfeitamente
        switch ($profile->calculation_type) {
            // ... (o resto do método continua igual)
            case 'SIMPLE_FIXED':
                return Money::of($profile->fixed_fee ?? 0, 'BRL');

            case 'GREATER_OF_BASE_PERCENTAGE':
                $baseFee = Money::of($profile->base_fee ?? 0, 'BRL');
                $percentageFee = $profile->percentage_fee ? $transactionValue->multipliedBy($profile->percentage_fee)->dividedBy(100, RoundingMode::UP) : Money::of(0, 'BRL');
                return $baseFee->isGreaterThan($percentageFee) ? $baseFee : $percentageFee;

            case 'TIERED':
                $tier = $profile->tiers()
                    ->where('min_value', '<=', $transactionValue->getAmount()->toFloat())
                    ->where(function ($query) use ($transactionValue) {
                        $query->where('max_value', '>=', $transactionValue->getAmount()->toFloat())
                            ->orWhereNull('max_value');
                    })
                    ->orderBy('priority', 'desc')
                    ->first();

                if (!$tier) {
                    Log::warning('Nenhuma faixa de taxa encontrada para o valor ' . $transactionValue->getAmount()->toFloat() . ' no perfil ' . $profile->id);
                    return Money::of(0, 'BRL');
                }

                $tierFixedFee = Money::of($tier->fixed_fee ?? 0, 'BRL');
                $tierPercentageFee = $tier->percentage_fee ? $transactionValue->multipliedBy($tier->percentage_fee)->dividedBy(100, RoundingMode::UP) : Money::of(0, 'BRL');
                return $tierFixedFee->plus($tierPercentageFee);

            default:
                Log::error('Tipo de cálculo desconhecido: ' . $profile->calculation_type);
                return Money::of(0, 'BRL');
        }
    }
}
