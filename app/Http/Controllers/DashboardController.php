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



class DashboardController extends Controller
{

    public function index(Request $request)
    {
        $loggedInUser = Auth::user();
        $accountsForSelector = collect(); // Inicia uma coleção vazia
        $selectedAccount = null;

        // =======================================================
        // --- 1. LÓGICA DE SELEÇÃO DE CONTA (O NOVO "CÉREBRO") ---
        // =======================================================

        if ($loggedInUser->isAdmin()) {
            // Admin pode ver todas as contas
            $accountsForSelector = Account::orderBy('name')->get();
            // Pega o ID da conta da request ou da sessão, se existir
            $selectedAccountId = $request->input('account_id', session('selected_account_id'));
            // Tenta encontrar a conta na lista de contas que ele pode ver
            $selectedAccount = $accountsForSelector->firstWhere('id', $selectedAccountId);
        } else { // É um Cliente
            // Cliente só vê a primeira conta à qual pertence
            $selectedAccount = $loggedInUser->accounts()->first();
        }

        // Se após todas as verificações nenhuma conta foi selecionada (ex: primeiro acesso do admin/sócio),
        // seleciona a primeira da lista como padrão.
        if (!$selectedAccount && $accountsForSelector->isNotEmpty()) {
            $selectedAccount = $accountsForSelector->first();
        }

        // Se, mesmo assim, não houver conta (ex: um cliente sem conta), exibe uma view de erro ou boas-vindas.
        if (!$selectedAccount) {
            return view('dashboard.no-account'); // Crie esta view para lidar com este caso
        }

        // Salva a última conta selecionada na sessão para persistência
        session(['selected_account_id' => $selectedAccount->id]);

        $allBalances = $selectedAccount->balances()->with('bank')->get();

        // 2. Inicia as variáveis que vamos exibir no dashboard.
        $withdrawableBalance = 0;  // O que pode sacar agora (do adquirente padrão)
        $otherActiveBalance = 0;   // O que está em outros adquirentes ativos ("congelado")
        $totalBlocked = 0;         // O que está bloqueado em qualquer adquirente ativo



        // 3. Itera sobre os saldos para separá-los e somá-los corretamente.
        foreach ($allBalances as $balance) {
            // Pula para o próximo se o banco relacionado não existir ou estiver inativo.
            if (!$balance->bank || !$balance->bank->active) {
                continue;
            }

            // Soma o saldo bloqueado de todos os adquirentes ativos.
            $totalBlocked += $balance->blocked_balance;



            // Verifica se o saldo pertence ao adquirente PADRÃO da conta.
            if ((int)$balance->acquirer_id === (int)$selectedAccount->acquirer_id) {
                $withdrawableBalance += $balance->available_balance;
            } else {
                $otherActiveBalance += $balance->available_balance;
            }
        }



        // 4. Monta o array final com os dados corretos.
        $balanceData = [
            'withdrawable' => $withdrawableBalance,
            'other_active' => $otherActiveBalance,
            'blocked'      => $totalBlocked,
            'total'        => $withdrawableBalance + $otherActiveBalance + $totalBlocked,
        ];

        $startDate = now()->startOfDay();

        // Query base para as transações da conta selecionada nas últimas 24h
        $baseKpiQuery = $selectedAccount->payments()->where('created_at', '>=', $startDate);

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

        // KPIs for PROFIT SUMMARY (only for Admins)
        $profitSummary = null;
        if ($loggedInUser->isAdmin()) {
            $profitSummary = [
                'total_fees' => $kpiIn['total_fees'] + $kpiOut['total_fees'],
                'net_profit' => $baseKpiQuery->clone()->where('status', 'paid')->sum('platform_profit'),
            ];
        }




        // Busca as chaves PIX da conta selecionada
        $pixKeys = $selectedAccount->pixKeys()->get();

        // QUERY DAS TRANSAÇÕES COM FILTROS (sua lógica original, agora aplicada à conta correta)
        $transactionsQuery = $selectedAccount->payments()->latest();

        // Aplicando todos os seus filtros
        if ($request->filled('status')) {
            $transactionsQuery->where('status', $request->status);
        }
        if ($request->filled('type_transaction')) {
            $transactionsQuery->where('type_transaction', $request->type_transaction);
        }

        if ($request->filled('date_filter')) {
            $days = $request->date_filter;
            if ($days != 'all') {
                $transactionsQuery->where('created_at', '>=', now()->subDays($days));
            }
        }

        // Filtro por valor mínimo
        if ($request->filled('amount_min')) {
            $transactionsQuery->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $transactionsQuery->where('amount', '<=', $request->amount_max);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;

            $transactionsQuery->where(function ($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('external_payment_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('provider_transaction_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('document', 'LIKE', "%{$searchTerm}%");
            });
        }

        $recentTransactions = $transactionsQuery->paginate(10)->withQueryString(); // withQueryString() mantém os filtros na paginação


        // =======================================================
        // --- 3. RETORNO PARA A VIEW ---
        // =======================================================

        return view('dashboard.index', [
            'loggedInUser'        => $loggedInUser,
            'selectedAccount'     => $selectedAccount,
            'accountsForSelector' => $accountsForSelector,
            'balanceData'         => $balanceData,
            'recentTransactions'  => $recentTransactions,
            'pixKeys'             => $pixKeys,
            'kpiIn'               => $kpiIn,           // <-- NOVO
            'kpiOut'              => $kpiOut,          // <-- NOVO
            'profitSummary'       => $profitSummary,
        ]);
    }

    public function selectAccount(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        // Lógica de segurança (opcional mas recomendado):
        // Garante que o admin/sócio só pode ver contas que ele tem permissão
        // if (Auth::user()->cannot('view', Account::find($validated['account_id']))) {
        //     abort(403);
        // }

        // Salva o ID da conta selecionada na sessão do usuário
        session(['selected_account_id' => $validated['account_id']]);

        // Redireciona de volta para o dashboard, que agora usará o novo ID da sessão
        return redirect()->route('dashboard');
    }

    /**
     * Métricas das transações confirmadas
     * Por padrão últimas 24 horas
     */
    private function getConfirmedMetrics(string $period, int $accountId): array
    {
        // Definir período
        $startDate = match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        // =======================================================
        // == CORREÇÃO APLICADA AQUI ==
        // =======================================================
        // A consulta agora filtra pelo ID da conta fornecido
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

    /**
     * Endpoint AJAX para atualizar as métricas do dashboard.
     * Este método agora é responsável por identificar a conta correta.
     */
    public function getMetrics(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', '24h');

        // Lógica para determinar qual conta visualizar (a mesma que usamos no método index)
        $accountId = session('selected_account_id');

        // Se não houver conta na sessão (ou seja, não é um admin vendo outra conta),
        // usa a primeira conta do próprio usuário.
        if (!$accountId && $user->accounts->isNotEmpty()) {
            $accountId = $user->accounts->first()->id;
        }

        // Se, por algum motivo, não conseguirmos determinar uma conta, retorna erro.
        if (!$accountId) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        // Chama a função de busca, passando o ID da conta correta
        $metrics = $this->getConfirmedMetrics($period, $accountId);

        return response()->json($metrics);
    }
}
