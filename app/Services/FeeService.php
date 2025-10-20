<?php

namespace App\Services;

use App\Models\User;
use App\Models\Fee;
use App\Models\UserFee;
use App\Models\Bank;
use Brick\Money\Money;
use Brick\Math\RoundingMode;

class FeeService
{
    /**
     * Calcula a taxa para uma transação específica com base na hierarquia de regras.
     *
     * @param User $user Usuário que está realizando a transação
     * @param Money $amount Valor da transação
     * @param string $type Tipo da transação (IN ou OUT)
     * @return array Informações detalhadas sobre a taxa calculada
     */
    public function calculateTransactionFee(User $user, Money $amount, string $type): array
    {
        $fee = Fee::whereHas('userFees', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('status', '1')
                ->where('is_default', true);
        })
            ->where('is_active', true)
            ->first();

        // Se não encontrou taxa padrão, busca por tipo específico
        if (!$fee) {
            $fee = Fee::where('type', $type)
                ->whereHas('userFees', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('status', '1')
                        ->where('is_default', false); // Garante que não pegue a padrão
                })
                ->where('is_active', true)
                ->first();
        }

        // 3. Se ainda assim não encontrar nenhuma regra, retorna uma taxa de segurança.
        if (!$fee) {
            // Esta taxa de fallback pode ser definida em um arquivo de configuração.
            return [
                'fee_id' => null,
                'type' => 'fallback',
                'fixed_fee' => Money::of(0, 'BRL'),
                'percentage' => 0,
                'minimum_fee' => Money::of(0.30, 'BRL'),
                'applied_fee' => Money::of(0.30, 'BRL')
            ];
        }

        $appliedFee = Money::of(0, 'BRL');


        // Se uma taxa fixa for definida e for maior que zero, ela tem prioridade total.
        if (isset($fee->fixed_fee) && $fee->fixed_fee > 0) {
            $appliedFee = Money::of($fee->fixed_fee, 'BRL');
        } else {
            // Se não houver taxa fixa, aplica a lógica antiga (percentual vs. mínima)
            $percentageFee = $amount->multipliedBy($fee->percentage)->dividedBy(100, RoundingMode::UP);
            $minimumFee = Money::of($fee->minimum_fee, 'BRL');
            $appliedFee = $percentageFee->isGreaterThan($minimumFee) ? $percentageFee : $minimumFee;
        }

        // REVISÃO: Retornando um array mais completo para facilitar o logging e a exibição.
        return [
            'fee_id' => $fee->id,
            'type' => $fee->fixed_fee > 0 ? 'fixed' : 'variable',
            'fixed_fee' => Money::of($fee->fixed_fee, 'BRL'),
            'percentage' => (float) $fee->percentage,
            'minimum_fee' => Money::of($fee->minimum_fee, 'BRL'),
            'applied_fee' => $appliedFee
        ];
    }

    public function calculateTransactionCost(Bank $bank, string $type, Money $amount): Money
    {
        // Pega o bloco de configuração de taxas diretamente.
        $feesConfig = $bank->fees_config ?? [];

        $costRule = [];
        $transactionTypeKey = ($type === 'IN') ? 'deposit' : 'withdrawal';

        // ✅ CORREÇÃO: Acessa a chave correta ('deposit' ou 'withdrawal')
        if (isset($feesConfig[$transactionTypeKey])) {
            $costRule = $feesConfig[$transactionTypeKey];
        }

        // Se não há regra de custo para este tipo de transação, o custo é zero.
        if (empty($costRule)) {
            return Money::of(0, 'BRL');
        }

        // LÓGICA DE HIERARQUIA DE CUSTO (mantida, pois está correta)

        // Prioridade 1: Custo Fixo
        if (isset($costRule["fixed"]) && $costRule["fixed"] > 0) {
            return Money::of($costRule["fixed"], 'BRL');
        }

        // Prioridade 2: Custo Percentual vs. Custo Mínimo
        $percentageCost = Money::of(0, 'BRL');
        if (isset($costRule["percentage"]) && $costRule["percentage"] > 0) {
            $percentageCost = $amount->multipliedBy($costRule["percentage"])->dividedBy(100, RoundingMode::UP);
        }

        $minimumCost = Money::of($costRule["minimum"] ?? 0, 'BRL');

        return $percentageCost->isGreaterThan($minimumCost) ? $percentageCost : $minimumCost;
    }
}
