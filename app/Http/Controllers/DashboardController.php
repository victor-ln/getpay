<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DailyBalance;
use App\Models\MonthlySummary;
use App\Models\Payment;
use App\Models\User;
use App\Models\WeeklySummary;
use App\Models\Bank;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;



class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $loggedInUser = Auth::user();
        $accountsForSelector = collect();
        $selectedAccount = null;

        // === Lógica de seleção de conta (mantida) ===
        if ($loggedInUser->isAdmin()) {
            $accountsForSelector = Account::orderBy('name')->get();
            $selectedAccountId = $request->input('account_id', session('selected_account_id'));
            $selectedAccount = $accountsForSelector->firstWhere('id', $selectedAccountId);
        } else {
            $selectedAccount = $loggedInUser->accounts()->first();
        }

        if (!$selectedAccount && $accountsForSelector->isNotEmpty()) {
            $selectedAccount = $accountsForSelector->first();
        }

        if (!$selectedAccount) {
            return view('dashboard.no-account');
        }

        session(['selected_account_id' => $selectedAccount->id]);

        // === CORREÇÃO: Método único para aplicar filtros ===
        $transactionsQuery = $this->buildFilteredQuery($selectedAccount, $request);

        // Clona a query base para KPIs (mesmos filtros da tabela)
        $baseKpiQuery = clone $transactionsQuery;

        // === Saldos (mantido) ===
        $allBalances = $selectedAccount->balances()->with('bank')->get();
        $withdrawableBalance = 0;
        $otherActiveBalance = 0;
        $totalBlocked = 0;

        foreach ($allBalances as $balance) {
            if (!$balance->bank || !$balance->bank->active) {
                continue;
            }
            $totalBlocked += $balance->blocked_balance;
            if ((int)$balance->acquirer_id === (int)$selectedAccount->acquirer_id) {
                $withdrawableBalance += $balance->available_balance;
            } else {
                $otherActiveBalance += $balance->available_balance;
            }
        }

        $balanceData = [
            'withdrawable' => $withdrawableBalance,
            'other_active' => $otherActiveBalance,
            'blocked' => $totalBlocked,
            'total' => $withdrawableBalance + $otherActiveBalance + $totalBlocked,
        ];

        // === KPIs usando a mesma query filtrada ===
        // KPIs for PAY IN
        $kpiInQuery = $baseKpiQuery->clone()->where('type_transaction', 'IN');
        $kpiInPaidQuery = $kpiInQuery->clone()->where('status', 'paid');

        $kpiIn = [
            'total_transactions' => $kpiInQuery->count(),
            'paid_transactions' => $kpiInPaidQuery->clone()->count(),
            'paid_volume' => $kpiInPaidQuery->clone()->sum('amount'),
            'total_fees' => $kpiInPaidQuery->clone()->sum('fee'),
        ];

        // KPIs for PAY OUT
        $kpiOutQuery = $baseKpiQuery->clone()->where('type_transaction', 'OUT');
        $kpiOutPaidQuery = $kpiOutQuery->clone()->where('status', 'paid');

        $kpiOut = [
            'total_transactions' => $kpiOutQuery->count(),
            'paid_transactions' => $kpiOutPaidQuery->clone()->count(),
            'paid_volume' => $kpiOutPaidQuery->clone()->sum('amount'),
            'total_fees' => $kpiOutPaidQuery->clone()->sum('fee'),
        ];

        // KPIs for PROFIT SUMMARY
        $profitSummary = null;
        if ($loggedInUser->isAdmin()) {
            $profitSummary = [
                'total_fees' => $kpiIn['total_fees'] + $kpiOut['total_fees'],
                'net_profit' => $baseKpiQuery->clone()->where('status', 'paid')->sum('platform_profit'),
            ];
        }

        // Busca as chaves PIX
        $pixKeys = $selectedAccount->pixKeys()->get();

        // === Transações para a tabela (usando a mesma query) ===
        $recentTransactions = $transactionsQuery->latest()->paginate(10)->withQueryString();

        $kpiPeriod = $this->getKpiPeriodLabel($request);

        return view('dashboard.index', [
            'loggedInUser' => $loggedInUser,
            'selectedAccount' => $selectedAccount,
            'accountsForSelector' => $accountsForSelector,
            'balanceData' => $balanceData,
            'recentTransactions' => $recentTransactions,
            'pixKeys' => $pixKeys,
            'kpiIn' => $kpiIn,
            'kpiOut' => $kpiOut,
            'profitSummary' => $profitSummary,
            'kpiPeriod' => $kpiPeriod,
        ]);
    }

    /**
     * NOVO MÉTODO: Constrói a query com todos os filtros aplicados
     * Usado tanto para KPIs quanto para a tabela de transações
     */
    private function buildFilteredQuery($selectedAccount, Request $request)
    {
        $query = $selectedAccount->payments();

        // Filtro de status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro de tipo de transação
        if ($request->filled('type_transaction')) {
            $query->where('type_transaction', $request->type_transaction);
        }

        // Filtros de data
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('updated_at', [$startDate, $endDate]);
        } elseif ($request->filled('date_filter')) {
            $days = $request->date_filter;
            if ($days != 'all') {
                $query->where('updated_at', '>=', now()->subDays($days));
            }
        }

        // Filtros de valor
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        // Filtro de busca
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('external_payment_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('provider_transaction_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('document', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query;
    }

    // === Resto dos métodos mantidos ===
    public function selectAccount(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        session(['selected_account_id' => $validated['account_id']]);
        return redirect()->route('dashboard');
    }

    private function getConfirmedMetrics(string $period, int $accountId): array
    {
        $startDate = match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $confirmedTransactions = \App\Models\Payment::where('account_id', $accountId)
            ->where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'volume' => $confirmedTransactions->sum('amount') ?? 0,
            'fee' => $confirmedTransactions->sum('fee') ?? 0,
            'quantity' => $confirmedTransactions->count(),
            'period' => $period
        ];
    }

    public function getMetrics(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', '24h');
        $accountId = session('selected_account_id');

        if (!$accountId && $user->accounts->isNotEmpty()) {
            $accountId = $user->accounts->first()->id;
        }

        if (!$accountId) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $metrics = $this->getConfirmedMetrics($period, $accountId);
        return response()->json($metrics);
    }

    public function export(Request $request)
    {
        $fileName = 'transactions-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new TransactionsExport($request), $fileName);
    }

    private function getKpiPeriodLabel(Request $request): string
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->format('d/m/Y');
            $endDate = Carbon::parse($request->end_date)->format('d/m/Y');
            return " {$startDate} - {$endDate}";
        }

        if ($request->filled('date_filter')) {
            $days = $request->date_filter;
            return match ($days) {
                '1' => 'Last 24 hours',
                '7' => 'Last 7 days',
                '30' => 'Last 30 days',
                'all' => 'All Time',
                default => 'Last 24 hours'
            };
        }

        return 'Today';
    }
}
