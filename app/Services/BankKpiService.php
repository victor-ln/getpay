<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\Payment;
use App\Models\PlatformAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BankKpiService
{
    /**
     * Calcula os KPIs para um banco específico, usando cache para performance.
     *
     * @param Bank $bank
     * @return array
     */
    public function getKpis(Bank $bank): array
    {
        // Chave única de cache para este banco, válida por 30 minutos
        $cacheKey = "bank_kpis_{$bank->id}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($bank) {

            $now = Carbon::now();

            // Query base para transações pagas relacionadas a este banco
            $baseQuery = Payment::where('provider_id', $bank->id)->where('status', 'paid');

            // --- KPIs de Volume de Transações (Queries Rápidas) ---
            $inThisMonth = (clone $baseQuery)->where('type_transaction', 'IN')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('amount');
            $inThisWeek = (clone $baseQuery)->where('type_transaction', 'IN')->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->sum('amount');
            $inThisYear = (clone $baseQuery)->where('type_transaction', 'IN')->whereYear('created_at', $now->year)->sum('amount');
            $outThisMonth = (clone $baseQuery)->where('type_transaction', 'OUT')->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('amount');

            // --- KPIs de Saldo e Taxas (Fontes de Dados Corretas) ---

            // "Current Balance" agora vem da nossa tabela de contas da plataforma.
            $platformAccount = PlatformAccount::where('bank_id', $bank->id)->first();
            $currentBalance = $platformAccount ? $platformAccount->current_balance : 0;

            // "Total Fees Paid" agora é uma soma precisa da coluna 'fee'.
            $totalFeesPaid = (clone $baseQuery)->sum('fee');

            return [
                'in_this_month' => $inThisMonth,
                'in_this_week' => $inThisWeek,
                'in_this_year' => $inThisYear,
                'out_this_month' => $outThisMonth,
                'current_balance' => $currentBalance,
                'total_fees_paid' => $totalFeesPaid,
            ];
        });
    }
}
