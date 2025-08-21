<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DailyBalance;
use App\Models\MonthlySummary;
use App\Models\Payment;
use App\Models\User;
use App\Models\WeeklySummary;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class DashboardController extends Controller
{

    public function index(Request $request)
    {


        $user = Auth::user();



        $selectedAccountId = session('selected_account_id');

        // Se NADA for encontrado na sessão...
        if (!$selectedAccountId) {
            // ...verificamos se o usuário tem PELO MENOS UMA conta associada.
            // O método isNotEmpty() é uma forma segura de verificar.
            if ($user->accounts->isNotEmpty()) {
                // ...e então pegamos o ID da PRIMEIRA conta dessa lista como padrão.
                $selectedAccountId = $user->accounts->first()->id;
            }
        }

        // Busca a conta que será exibida
        // Adicione uma verificação de permissão aqui se necessário
        $selectedAccount = Account::find($selectedAccountId);

        // Se por algum motivo a conta não for encontrada, volta para a do usuário
        if (!$selectedAccount) {
            $selectedAccount = $user->account;
        }

        // Busca todos os dados necessários para a view
        $accountsForSelector = Account::orderBy('name')->get(); // Para popular o dropdown do admin
        $balanceData = [
            'available' => $selectedAccount->balance->available_balance ?? 0,
            'blocked' => $selectedAccount->balance->blocked_balance ?? 0,
        ];

        $transactionMetrics = [ // Exemplo de busca de métricas
            'totalCount' => $selectedAccount->payments()->count(),
            'paidLast24h' => $selectedAccount->payments()->where('status', 'paid')->where('created_at', '>=', now()->subHours(24))->count(),
        ];

        // QUERY DAS TRANSAÇÕES COM FILTROS
        $transactionsQuery = $selectedAccount->payments()->latest();

        // Filtro por Status
        if ($request->filled('status')) {
            $transactionsQuery->where('status', $request->status);
        }

        // Filtro por Tipo de Transação
        if ($request->filled('type_transaction')) {
            $transactionsQuery->where('type_transaction', $request->type_transaction);
        }

        // Filtro por Data (últimos X dias)
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

        $recentTransactions = $transactionsQuery->paginate(10);



        $pixKeys = $selectedAccount->pixKeys()->get();



        // Passa todas as variáveis para a view
        return view('dashboard.index', [
            'selectedAccount' => $selectedAccount,
            'accountsForSelector' => $accountsForSelector,
            'balanceData' => $balanceData,
            'transactionMetrics' => $transactionMetrics,
            'recentTransactions' => $recentTransactions,
            'pixKeys' => $pixKeys,
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
