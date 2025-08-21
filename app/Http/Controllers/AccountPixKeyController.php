<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountPixKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Policies\PixKeyPolicy;

class AccountPixKeyController extends Controller
{




    public function store(Request $request)
    {
        // 1. Pega o usuário logado
        $user = Auth::user();

        // 2. Valida os dados, incluindo o ID da conta que está sendo gerenciada
        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'], // <-- NOVO: Exige que a view informe a conta
            'type' => ['required', 'string', Rule::in(['CPF', 'EMAIL', 'PHONE', 'EVP', 'CNPJ'])],
            'key'  => ['required', 'string', 'max:255'],
        ]);

        // 3. Busca a conta que queremos modificar a partir do ID enviado
        $account = Account::find($validated['account_id']);

        // 4. VERIFICAÇÃO DE PERMISSÃO (A Lógica Correta)
        // Você pode usar uma Policy do Laravel para isso, que é a melhor prática.
        // Ex: $this->authorize('update', $account);
        // Mas, fazendo manualmente, a lógica seria:
        $canManage = false;
        if ($user->level === 'admin') {
            $canManage = true; // Admin pode tudo
        } elseif ($user->level === 'client') {
            // Sócio só pode gerenciar contas que ele indicou
            if ($user->accounts()->whereKey($account->id)->exists()) {
                $canManage = true;
            }
        }

        if (!$canManage) {
            return response()->json(['message' => 'Você não tem permissão para gerenciar esta conta.'], 403);
        }

        // 5. O resto da sua lógica já estava perfeita!
        // Verifica se a conta já não possui esta chave
        if ($account->pixKeys()->where('key', $validated['key'])->exists()) {
            return response()->json(['message' => 'Esta chave PIX já está registrada para esta conta.'], 422);
        }

        // Cria a chave PIX associada à conta
        $pixKey = $account->pixKeys()->create([
            'type' => $validated['type'],
            'key' => $validated['key'],
            'status' => 'active',
            'created_by' => $user->id, // Ótimo para auditoria
        ]);

        // Retorna a chave recém-criada para o frontend
        return response()->json([
            'success' => true,
            'data' => $pixKey,
            'message' => 'Chave PIX criada com sucesso.'
        ], 201);
    }

    /**
     * Remove (soft delete) uma chave PIX específica.
     */
    public function destroy(AccountPixKey $pixKey)
    {
        // Autoriza a ação usando a policy (garante que o usuário só pode deletar suas próprias chaves)
        $this->authorize('delete', $pixKey);

        $pixKey->delete(); // Executa o soft delete

        return response()->json(['message' => 'PIX key removed successfully.']);
    }
}
