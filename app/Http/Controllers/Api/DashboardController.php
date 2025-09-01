<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{

    public function getDashboardData(Request $request)
    {
        $loggedInUser = Auth::user();
        $accountsForSelector = collect();
        $selectedAccount = null;


        if ($loggedInUser->level === 'admin') {
            $accountsForSelector = Account::orderBy('name')->get(['id', 'name']);
        } elseif ($loggedInUser->level === 'partner') {
            $accountsForSelector = Account::where('partner_id', $loggedInUser->id)->orderBy('name')->get(['id', 'name']);
        } else {
            $selectedAccount = $loggedInUser->accounts()->first();
        }


        if ($accountsForSelector->isNotEmpty()) {
            $requestedAccountId = $request->input('account_id');

            if ($requestedAccountId) {

                $selectedAccount = $accountsForSelector->firstWhere('id', $requestedAccountId);
            } else {

                $selectedAccount = $accountsForSelector->first();
            }
        }


        $balance = null;
        $pixKeys = collect();
        $payments = null;
        $minTransactionValue = 1.00;

        if ($selectedAccount) {

            $selectedAccount->load('balance', 'pixKeys');

            $balance = $selectedAccount->balance;
            $pixKeys = $selectedAccount->pixKeys->map->only(['type', 'key']);
            $minTransactionValue = $selectedAccount->min_amount_transaction;


            $query = Payment::where('account_id', $selectedAccount->id);


            $this->applyFilters($query, $request);


            $limit = $request->input('limit');
            if ($limit === 'all') {
                $payments = $query->latest()->get();
            } else {
                $payments = $query->latest()->paginate($limit ?: 10);
            }
        }



        $data = [
            'loggedInUser' => [
                'id' => $loggedInUser->id,
                'name' => $loggedInUser->name,
                'level' => $loggedInUser->level,
                'document' => $loggedInUser->document,
                'twoFactorEnabled' => !is_null($loggedInUser->two_factor_secret),
            ],
            'accountsForSelector' => $accountsForSelector,
            'selectedAccount' => $selectedAccount ? [
                'id' => $selectedAccount->id,
                'name' => $selectedAccount->name,
                'balance' => $balance ? ['available' => $balance->available_balance, 'blocked' => $balance->blocked_balance] : ['available' => 0, 'blocked' => 0],
                'minTransactionValue' => $minTransactionValue,
                'registeredPixKeys' => $pixKeys,
                'payments' => $payments ? ($limit === 'all' ? $payments : $payments->items()) : [],
                'pagination' => $payments && $limit !== 'all' ? [
                    'currentPage' => $payments->currentPage(),
                    'lastPage' => $payments->lastPage(),
                    'total' => $payments->total(),
                ] : null,
            ] : null,
        ];

        return response()->json($data);
    }

    /**
     * Método auxiliar para aplicar filtros de transação.
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        $query->when($request->input('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        $query->when($request->input('type'), function ($q, $type) {
            return $q->where('type_transaction', $type);
        });

        $query->when($request->input('date_from'), function ($q, $date_from) {
            return $q->whereDate('created_at', '>=', $date_from);
        });

        $query->when($request->input('date_to'), function ($q, $date_to) {
            return $q->whereDate('created_at', '<=', $date_to);
        });
    }
}
