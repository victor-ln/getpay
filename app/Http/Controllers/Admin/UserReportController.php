<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserReportController extends Controller
{
    /**
     * Exibe a página principal de relatórios de utilizadores.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedAccount = null;

        if ($user->isAdmin()) {
            $accountsForSelector = Account::orderBy('name')->get();
            $selectedAccountId = $request->input('account_id', session('selected_account_id'));
            $selectedAccount = Account::find($selectedAccountId);

            if (!$selectedAccount && $accountsForSelector->isNotEmpty()) {
                $selectedAccount = $accountsForSelector->first();
            }
        } else {
            $selectedAccount = $user->accounts()->first();
        }

        if (!$selectedAccount) {
            return view('dashboard.no-account');
        }

        $byAccountData = $this->getByAccountData($request, true);

        return view('admin.user_reports.index', [
            'byAccountData' => $byAccountData,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Busca dados para a aba "Relatório por Conta".
     */
    public function getByAccountData(Request $request, $isInitialLoad = false)
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            $accountId = session('selected_account_id');
        } else {
            $accountId = $user->accounts()->first()->id;
        }

        $query = Payment::query()
            ->where('account_id', $accountId)
            ->whereNotNull('document')
            ->select(
                'name',
                'document',
                DB::raw("SUM(CASE WHEN type_transaction = 'IN' THEN amount ELSE 0 END) as volume_in"),
                DB::raw("COUNT(CASE WHEN type_transaction = 'IN' THEN 1 END) as count_in"),
                DB::raw("SUM(CASE WHEN type_transaction = 'OUT' THEN amount ELSE 0 END) as volume_out"),
                DB::raw("COUNT(CASE WHEN type_transaction = 'OUT' THEN 1 END) as count_out")
            )
            ->groupBy('name', 'document');

        $data = $query->paginate(50);

        if ($request->ajax() && !$isInitialLoad) {
            $html = view('_partials.reports.by-account-table', ['data' => $data])->render();
            return response()->json(['html' => $html]);
        }

        return $data;
    }

    /**
     * Busca dados para a aba "Análise Multi-Contas" (apenas para admins).
     */
    public function getMultiAccountData(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        try {
            $query = Payment::query()
                ->whereNotNull('document')
                ->select(
                    'document',
                    DB::raw('COUNT(DISTINCT account_id) as associated_accounts_count'),
                    DB::raw('SUM(amount) as total_volume')
                )
                ->groupBy('document')
                ->havingRaw('COUNT(DISTINCT account_id) > 1');

            $data = $query->paginate(50);
            $html = view('_partials.reports.multi-account-table', ['data' => $data])->render();

            return response()->json([
                'html' => $html,
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Busca usuários por nome ou documento para análise individual.
     */
    public function searchUsers(Request $request)
    {
        $search = $request->input('search');

        if (empty($search) || strlen($search) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Digite pelo menos 3 caracteres para buscar'
            ]);
        }

        $users = Payment::query()
            ->whereNotNull('document')
            ->where(function ($query) use ($search) {
                $query->where('document', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->select('name', 'document')
            ->groupBy('name', 'document')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Análise detalhada de comportamento de um usuário específico.
     */
    public function getUserBehavior(Request $request)
    {
        $document = $request->input('document');

        if (empty($document)) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não informado'
            ], 400);
        }

        try {
            // Informações básicas do usuário
            $userInfo = Payment::where('document', $document)
                ->select('name', 'document')
                ->first();

            if (!$userInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], 404);
            }

            // Estatísticas de depósitos (IN)
            $depositsStats = Payment::where('document', $document)
                ->where('type_transaction', 'IN')
                ->select(
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(amount) as total_volume'),
                    DB::raw('AVG(amount) as average_amount'),
                    DB::raw('MAX(amount) as max_amount'),
                    DB::raw('MIN(amount) as min_amount')
                )
                ->first();

            // Estatísticas de saques (OUT)
            $withdrawalsStats = Payment::where('document', $document)
                ->where('type_transaction', 'OUT')
                ->select(
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(amount) as total_volume'),
                    DB::raw('AVG(amount) as average_amount'),
                    DB::raw('MAX(amount) as max_amount'),
                    DB::raw('MIN(amount) as min_amount')
                )
                ->first();

            // Contas associadas
            $associatedAccounts = Payment::where('document', $document)
                ->select('account_id')
                ->distinct()
                ->count();

            // Transações por conta
            $transactionsByAccount = Payment::where('document', $document)
                ->join('accounts', 'payments.account_id', '=', 'accounts.id')
                ->select(
                    'accounts.name as account_name',
                    'accounts.id as account_id',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(amount) as total_amount')
                )
                ->groupBy('accounts.id', 'accounts.name')
                ->get();

            // Atividade nos últimos 30 dias
            $recentActivity = Payment::where('document', $document)
                ->where('created_at', '>=', now()->subDays(30))
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw("SUM(CASE WHEN type_transaction = 'IN' THEN amount ELSE 0 END) as deposits"),
                    DB::raw("SUM(CASE WHEN type_transaction = 'OUT' THEN amount ELSE 0 END) as withdrawals"),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            // Últimas transações
            $lastTransactions = Payment::where('document', $document)
                ->with('account:id,name')
                ->select('id', 'account_id', 'type_transaction', 'amount', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Status das transações (distribuição)
            $statusDistribution = Payment::where('document', $document)
                ->select(
                    'status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as total_amount')
                )
                ->groupBy('status')
                ->get();

            // Primeira e última transação
            $firstTransaction = Payment::where('document', $document)
                ->orderBy('created_at', 'asc')
                ->first();

            $lastTransaction = Payment::where('document', $document)
                ->orderBy('created_at', 'desc')
                ->first();

            // Calcular saldo líquido (depósitos - saques)
            $netBalance = ($depositsStats->total_volume ?? 0) - ($withdrawalsStats->total_volume ?? 0);

            $behaviorData = [
                'user' => [
                    'name' => $userInfo->name,
                    'document' => $userInfo->document,
                ],
                'summary' => [
                    'associated_accounts' => $associatedAccounts,
                    'total_transactions' => ($depositsStats->total_count ?? 0) + ($withdrawalsStats->total_count ?? 0),
                    'net_balance' => $netBalance,
                    'first_transaction_date' => $firstTransaction?->created_at,
                    'last_transaction_date' => $lastTransaction?->created_at,
                ],
                'deposits' => [
                    'count' => $depositsStats->total_count ?? 0,
                    'total' => $depositsStats->total_volume ?? 0,
                    'average' => $depositsStats->average_amount ?? 0,
                    'max' => $depositsStats->max_amount ?? 0,
                    'min' => $depositsStats->min_amount ?? 0,
                ],
                'withdrawals' => [
                    'count' => $withdrawalsStats->total_count ?? 0,
                    'total' => $withdrawalsStats->total_volume ?? 0,
                    'average' => $withdrawalsStats->average_amount ?? 0,
                    'max' => $withdrawalsStats->max_amount ?? 0,
                    'min' => $withdrawalsStats->min_amount ?? 0,
                ],
                'accounts' => $transactionsByAccount,
                'recent_activity' => $recentActivity,
                'last_transactions' => $lastTransactions,
                'status_distribution' => $statusDistribution,
            ];

            $html = view('_partials.reports.user-behavior-detail', ['data' => $behaviorData])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => $behaviorData
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getUserBehavior', [
                'document' => $document,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados do usuário: ' . $e->getMessage()
            ], 500);
        }
    }
}
