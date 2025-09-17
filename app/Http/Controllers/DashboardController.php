<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Payment;
use App\Models\User;
use App\Models\Bank;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    /**
     * Exibe o dashboard principal.
     */
    public function index(Request $request)
    {
        $loggedInUser = Auth::user();
        $accountsForSelector = collect();
        $selectedAccount = null;

        // === Lógica de seleção de conta ===
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

        // === Constrói a query base com todos os filtros aplicados ===
        $transactionsQuery = $this->buildFilteredQuery($selectedAccount, $request);

        // Clona a query base para os KPIs (usará os mesmos filtros da tabela)
        $baseKpiQuery = clone $transactionsQuery;

        // === Cálculos para a View ===
        $balanceData = $this->calculateBalances($selectedAccount);
        $kpiIn = $this->calculateKpis($baseKpiQuery, 'IN');
        $kpiOut = $this->calculateKpis($baseKpiQuery, 'OUT');
        $profitSummary = $this->calculateProfitSummary($loggedInUser, $kpiIn, $kpiOut, $baseKpiQuery);
        $pixKeys = $selectedAccount->pixKeys()->get();
        $kpiPeriod = $this->getKpiPeriodLabel($request);

        // === Transações para a tabela (limitado a 50 por página) ===
        $recentTransactions = $transactionsQuery->latest()->paginate(50)->withQueryString();

        return view('dashboard.index', compact(
            'loggedInUser',
            'selectedAccount',
            'accountsForSelector',
            'balanceData',
            'recentTransactions',
            'pixKeys',
            'kpiIn',
            'kpiOut',
            'profitSummary',
            'kpiPeriod'
        ));
    }

    /**
     * Seleciona uma conta para visualização e a guarda na sessão.
     */
    public function selectAccount(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        session(['selected_account_id' => $validated['account_id']]);
        return redirect()->route('dashboard');
    }

    /**
     * Retorna métricas confirmadas para chamadas AJAX.
     */
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

    /**
     * Exporta as transações para um ficheiro Excel.
     */
    public function export(Request $request)
    {
        $fileName = 'transactions-' . now()->format('Y-m-d-His') . '.xlsx';
        return Excel::download(new TransactionsExport($request), $fileName);
    }

    // =======================================================
    // MÉTODOS PRIVADOS DE AJUDA
    // =======================================================

    private function buildFilteredQuery(Account $account, Request $request): Builder
    {
        $query = Payment::query()->where('account_id', $account->id);

        // ✅ [CORREÇÃO] Lógica de filtro de data com a prioridade correta
        $period = $request->input('date_filter', 'today');

        // A prioridade é para os filtros rápidos. Se um deles for selecionado (e não for "all"),
        // ele sobrepõe-se a qualquer `start_date` ou `end_date` que possa ter ficado na URL.
        if (in_array($period, ['today', 'yesterday', '7', '30'])) {
            switch ($period) {
                case 'yesterday':
                    $query->whereDate('created_at', now()->subDay());
                    break;
                case '7':
                    $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
                    break;
                case '30':
                    $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
                    break;
                case 'today':
                    $query->whereDate('created_at', now());
                    break;
            }
        }
        // Se nenhum filtro rápido for usado, então verificamos se há um intervalo de datas customizado.
        elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        // Se `date_filter` for 'all' ou outro valor, não aplicamos nenhum filtro de data.

        // Outros filtros...
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type_transaction')) {
            $query->where('type_transaction', $request->type_transaction);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('external_payment_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('provider_transaction_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('document', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('end_to_end_id', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query;
    }

    private function calculateBalances(Account $selectedAccount): array
    {
        $withdrawableBalance = 0;
        $otherActiveBalance = 0;
        $totalBlocked = 0;

        $allBalances = $selectedAccount->balances()->with('bank')->get();

        foreach ($allBalances as $balance) {
            if (!$balance->bank || !$balance->bank->active) continue;

            $totalBlocked += $balance->blocked_balance;
            if ((int)$balance->acquirer_id === (int)$selectedAccount->acquirer_id) {
                $withdrawableBalance += $balance->available_balance;
            } else {
                $otherActiveBalance += $balance->available_balance;
            }
        }

        return [
            'withdrawable' => $withdrawableBalance,
            'other_active' => $otherActiveBalance,
            'blocked' => $totalBlocked,
            'total' => $withdrawableBalance + $otherActiveBalance + $totalBlocked,
        ];
    }

    private function calculateKpis(Builder $baseQuery, string $type): array
    {
        $query = $baseQuery->clone()->where('type_transaction', $type);
        $paidQuery = $query->clone()->where('status', 'paid');

        return [
            'total_transactions' => $query->count(),
            'paid_transactions' => $paidQuery->clone()->count(),
            'paid_volume' => $paidQuery->clone()->sum('amount'),
            'total_fees' => $paidQuery->clone()->sum('fee'),
        ];
    }

    private function calculateProfitSummary(User $loggedInUser, array $kpiIn, array $kpiOut, Builder $baseKpiQuery): ?array
    {
        if ($loggedInUser->isAdmin()) {
            return [
                'total_fees' => $kpiIn['total_fees'] + $kpiOut['total_fees'],
                'net_profit' => $baseKpiQuery->clone()->where('status', 'paid')->sum('platform_profit'),
            ];
        }
        return null;
    }

    private function getKpiPeriodLabel(Request $request): string
    {
        $period = $request->input('date_filter', 'today');

        if ($request->filled('start_date') && $request->filled('end_date') && !in_array($period, ['today', 'yesterday', '7', '30'])) {
            $startDate = Carbon::parse($request->start_date)->format('d/m/Y');
            $endDate = Carbon::parse($request->end_date)->format('d/m/Y');
            return "{$startDate} to {$endDate}";
        }

        return match ($period) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7' => 'Last 7 Days',
            '30' => 'Last 30 Days',
            'all' => 'All Time',
            default => 'Filtered Period',
        };
    }

    private function getConfirmedMetrics(string $period, int $accountId): array
    {
        $startDate = match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $confirmedTransactions = Payment::where('account_id', $accountId)
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
}
