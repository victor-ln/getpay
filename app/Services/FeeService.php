<?php

namespace App\Services;

use App\Models\User;
use App\Models\Fee;
use App\Models\UserFee;
use App\Models\Bank;

class FeeService
{
    /**
     * Calcula a taxa para uma transação específica com base na hierarquia de regras.
     *
     * @param User $user Usuário que está realizando a transação
     * @param float $amount Valor da transação
     * @param string $type Tipo da transação (IN ou OUT)
     * @return array Informações detalhadas sobre a taxa calculada
     */
    public function calculateTransactionFee(User $user, float $amount, string $type): array
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
                'fixed_fee' => 0,
                'percentage' => 0, // Defina um valor padrão se desejar
                'minimum_fee' => 0.30, // Exemplo de taxa mínima de fallback
                'applied_fee' => 0.30
            ];
        }

        $appliedFee = 0;


        // Se uma taxa fixa for definida e for maior que zero, ela tem prioridade total.
        if (isset($fee->fixed_fee) && $fee->fixed_fee > 0) {
            $appliedFee = $fee->fixed_fee;
        } else {
            // Se não houver taxa fixa, aplica a lógica antiga (percentual vs. mínima)
            $percentageFee = ($amount * $fee->percentage) / 100;
            $appliedFee = max($percentageFee, $fee->minimum_fee);
        }

        // REVISÃO: Retornando um array mais completo para facilitar o logging e a exibição.
        return [
            'fee_id' => $fee->id,
            'type' => $fee->fixed_fee > 0 ? 'fixed' : 'variable',
            'fixed_fee' => (float) $fee->fixed_fee,
            'percentage' => (float) $fee->percentage,
            'minimum_fee' => (float) $fee->minimum_fee,
            'applied_fee' => (float) $appliedFee
        ];
    }

    public function calculateTransactionCost(Bank $bank, string $type, float $amount): float
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
            return 0.00;
        }

        // LÓGICA DE HIERARQUIA DE CUSTO (mantida, pois está correta)

        // Prioridade 1: Custo Fixo
        if (isset($costRule['fixed']) && $costRule['fixed'] > 0) {
            return (float) $costRule['fixed'];
        }

        // Prioridade 2: Custo Percentual vs. Custo Mínimo
        $percentageCost = 0;
        if (isset($costRule['percentage']) && $costRule['percentage'] > 0) {
            $percentageCost = ($amount * $costRule['percentage']) / 100;
        }

        $minimumCost = (float) ($costRule['minimum'] ?? 0);

        return max($percentageCost, $minimumCost);
    }
}
