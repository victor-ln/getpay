<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\PlatformAccount;
use Brick\Money\Money;
use Brick\Math\RoundingMode;

class PartnerPayoutService
{
    /**
     * Prepara todos os dados necessários para a view de organização de lucros dos sócios.
     *
     * @return array
     */
    public function getPayoutDashboardData(): array
    {
        // 1. Busca todos os sócios ativos
        $partners = Partner::where('is_active', true)->orderBy('name')->get();

        // 2. Calcula o valor líquido total disponível para distribuição
        $netForDistribution = $this->calculateNetForDistribution();

        // 3. Monta o array de estatísticas para o cabeçalho da página
        $headerStats = [
            // O saldo total é a soma dos saldos em todas as contas da plataforma
            'totalAccountBalance' => Money::of(PlatformAccount::sum('current_balance'), 'BRL'),
            // O custo já foi descontado para chegar ao lucro líquido
            'totalExpenses' => Money::of(0, 'BRL'), // Podemos aprimorar isso no futuro para mostrar os custos do período
            'netForDistribution' => $netForDistribution->getAmount()->toFloat(),
        ];

        // 4. Calcula a porcentagem total que já foi alocada aos sócios
        $totalPercentageDistributed = $partners->sum('receiving_percentage');

        // 5. Retorna todos os dados de forma organizada para a view
        return [
            'partners' => $partners,
            'headerStats' => $headerStats,
            'totalPercentageDistributed' => $totalPercentageDistributed,
        ];
    }

    /**
     * Calcula o lucro líquido total da plataforma, somando o lucro de cada adquirente.
     *
     * @return float
     */
    private function calculateNetForDistribution(): Money
    {
        $totalNetAvailable = Money::of(0, 'BRL');

        // Pega todas as contas da plataforma junto com a configuração de custo de seus bancos
        $platformAccounts = PlatformAccount::with('bank')->get();

        foreach ($platformAccounts as $account) {
            $currentBalance = Money::of($account->current_balance, 'BRL');
            $costsConfig = $account->bank->fees_config['acquirer_costs'] ?? [];

            // Lógica para calcular o custo operacional daquele saldo
            // Exemplo simples: um percentual sobre o saldo atual
            $operationalCostPercentage = $costsConfig['operational_percentage'] ?? 0;
            $cost = $currentBalance->multipliedBy($operationalCostPercentage)->dividedBy(100, RoundingMode::UP);

            $netProfit = $currentBalance->minus($cost);
            $totalNetAvailable = $totalNetAvailable->plus($netProfit);
        }

        return $totalNetAvailable;
    }
}
