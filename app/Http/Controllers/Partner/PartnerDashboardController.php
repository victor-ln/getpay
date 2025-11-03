<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use Carbon\Carbon;

class PartnerDashboardController extends Controller
{
    /**
     * Exibe o dashboard principal do Sócio (Partner).
     */
    public function dashboard(Request $request)
    {
        $partner = Auth::user();

        // 1. Busca todas as contas que este sócio gere
        // (Usando a relação 'sharedProfitAccounts' que já definimos no User model)
        $managedAccounts = $partner->sharedProfitAccounts()->orderBy('name')->get();

        // 2. Prepara os KPIs globais do Sócio (com dados de exemplo por agora)
        $kpis = [
            'total_referred_accounts' => $managedAccounts->count(),
            'total_commission_all_time' => 0.00, // Lógica real virá na Fase 2
            'commission_this_month' => 0.00,
            'volume_this_month' => 0.00,
        ];

        // 3. Prepara os dados por conta (a "visão não misturada")
        // Por agora, vamos apenas passar a lista. A lógica de KPI por conta
        // virá na Fase 2 para manter a página rápida.
        $accountsData = $managedAccounts;

        return view('partners.indicate.dashboard', [
            'partner' => $partner,
            'kpis' => $kpis,
            'accountsData' => $accountsData,
        ]);
    }
}
