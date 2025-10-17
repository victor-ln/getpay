<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BalanceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BalanceHistoryController extends Controller
{
    /**
     * Display the balance history for the currently selected account.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $accountsForSelector = collect(); // Inicia como coleção vazia
        $selectedAccount = null;

        // --- LÓGICA DE SELEÇÃO DE CONTA ---
        if ($user->isAdmin()) {
            $accountsForSelector = Account::orderBy('name')->get();
            // A conta selecionada pode vir do filtro, ou da sessão (como no dashboard)
            $selectedAccountId = $request->input('account_id', session('selected_account_id'));
            $selectedAccount = Account::find($selectedAccountId);

            // Fallback para a primeira conta se nenhuma for selecionada
            if (!$selectedAccount && $accountsForSelector->isNotEmpty()) {
                $selectedAccount = $accountsForSelector->first();
            }
        } else {
            $selectedAccount = $user->accounts()->first();
        }

        if (!$selectedAccount) {
            return redirect()->route('dashboard')->with('error', 'No account available to display statement.');
        }
        // Guarda a última conta selecionada na sessão para consistência
        session(['selected_account_id' => $selectedAccount->id]);


        // --- LÓGICA DE FILTROS ---
        $query = BalanceHistory::where('account_id', $selectedAccount->id)
            ->with(['payment', 'bank']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', "%{$searchTerm}%")
                    ->orWhereHas('payment', function ($paymentQuery) use ($searchTerm) {
                        $paymentQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('document', 'like', "%{$searchTerm}%")
                            ->orWhere('external_payment_id', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $balanceHistory = $query->latest()->paginate(25)->withQueryString();

        return view('balance_history.index', [
            'selectedAccount' => $selectedAccount,
            'balanceHistory' => $balanceHistory,
            'accountsForSelector' => $accountsForSelector, // Envia a lista de contas para o filtro do admin
        ]);
    }
}
