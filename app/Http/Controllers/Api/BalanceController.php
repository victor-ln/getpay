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



        // 2. Validações de Segurança
        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        // Garante que o usuário logado tem permissão para ver esta conta
        // (Esta é uma verificação de segurança crucial)
        if (!$user->accounts->contains($account->id) && !$user->isAdmin()) { // Supondo um método isAdmin() no seu model User
            return response()->json(['message' => 'Unauthorized access to this account.'], 403);
        }

        // 3. Busca o saldo usando o relacionamento
        $balance = $account->balance;

        // 4. Monta e retorna a resposta JSON
        return response()->json([
            'success' => true,
            'data' => [
                'available_balance' => $balance->available_balance ?? 0.00,
                'blocked_balance' => $balance->blocked_balance ?? 0.00,
            ]
        ]);
    }
}
