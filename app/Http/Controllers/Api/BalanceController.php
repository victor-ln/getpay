<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    /**
     * Retorna o saldo disponível e bloqueado de uma conta.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        $user = Auth::user();
        $account = $user->accounts()->first();

        // 1. Validações de Segurança (mantidas)
        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }
        if (!$user->accounts->contains($account->id) && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized access to this account.'], 403);
        }

        // --- [INÍCIO DA LÓGICA CORRETA E DETALHADA] ---

        // 2. Pega o saldo do adquirente padrão (o que é SACÁVEL)
        $currentAcquirerBalance = $account->getCurrentAcquirerBalance();
        $withdrawableBalance = $currentAcquirerBalance->available_balance ?? 0;

        // 3. Pega o saldo total disponível (somando todos os adquirentes ATIVOS)
        $totalAvailableBalance = $account->total_available_balance;

        // 4. Calcula os "outros" saldos que não são imediatamente sacáveis
        $otherActiveBalances = $totalAvailableBalance - $withdrawableBalance;

        // 5. Calcula o total bloqueado em todos os adquirentes ativos
        $totalBlocked = $account->balances()
            ->whereHas('bank', function ($query) {
                $query->where('active', true);
            })
            ->sum('blocked_balance');

        // --- [FIM DA LÓGICA CORRETA] ---

        // 6. Monta e retorna a resposta JSON ESTRUTURADA
        return response()->json([
            'success' => true,
            'data' => [
                'withdrawable_balance'    => $withdrawableBalance,
                'other_active_balances'   => $otherActiveBalances,
                'total_available_balance' => $totalAvailableBalance,
                'blocked_balance'         => $totalBlocked,
            ]
        ]);
    }
}
