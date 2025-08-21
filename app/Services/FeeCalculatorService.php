<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FeeProfile;
use Illuminate\Support\Facades\Log;

class FeeCalculatorService
{
    /**
     * Calcula a taxa para uma determinada conta, valor e tipo de transação.
     *
     * @param Account $account A conta que está realizando a transação.
     * @param float $transactionValue O valor da transação.
     * @param string $transactionType O tipo de transação ('IN' ou 'OUT').
     * @return float A taxa calculada.
     */
    public function calculate(Account $account, float $transactionValue, string $transactionType): float
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
            return 0.00;
        }

        // A partir daqui, a lógica do switch-case que já tínhamos funciona perfeitamente
        switch ($profile->calculation_type) {
            // ... (o resto do método continua igual)
            case 'SIMPLE_FIXED':
                return (float) ($profile->fixed_fee ?? 0.00);

            case 'GREATER_OF_BASE_PERCENTAGE':
                $baseFee = (float) ($profile->base_fee ?? 0.00);
                $percentageFee = $profile->percentage_fee ? ($transactionValue * $profile->percentage_fee) / 100 : 0.00;
                return max($baseFee, $percentageFee);

            case 'TIERED':
                $tier = $profile->tiers()
                    ->where('min_value', '<=', $transactionValue)
                    ->where(function ($query) use ($transactionValue) {
                        $query->where('max_value', '>=', $transactionValue)
                            ->orWhereNull('max_value');
                    })
                    ->orderBy('priority', 'desc')
                    ->first();

                if (!$tier) {
                    Log::warning('Nenhuma faixa de taxa encontrada para o valor ' . $transactionValue . ' no perfil ' . $profile->id);
                    return 0.00;
                }

                $tierFixedFee = (float) ($tier->fixed_fee ?? 0.00);
                $tierPercentageFee = $tier->percentage_fee ? ($transactionValue * $tier->percentage_fee) / 100 : 0.00;
                return $tierFixedFee + $tierPercentageFee;

            default:
                Log::error('Tipo de cálculo desconhecido: ' . $profile->calculation_type);
                return 0.00;
        }
    }
}
