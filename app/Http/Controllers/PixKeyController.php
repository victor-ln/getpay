<?php

namespace App\Http\Controllers;

use App\Models\PixKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PixKeyController extends Controller
{
    /**
     * Armazena uma nova chave PIX para o usuário autenticado.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Autoriza a ação usando a policy
        $this->authorize('create', PixKey::class);

        // Valida os dados recebidos do formulário
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['CPF', 'EMAIL', 'PHONE', 'EVP', 'CNPJ'])],
            'key'  => ['required', 'string', 'max:255'],
        ]);

        // Verifica se o usuário já não possui esta chave
        if ($user->pixKeys()->where('key', $validated['key'])->exists()) {
            return response()->json(['message' => 'This PIX key is already registered.'], 422); // Unprocessable Entity
        }

        // Cria a chave PIX associada ao usuário
        $pixKey = $user->pixKeys()->create([
            'type' => $validated['type'],
            'key' => $validated['key'],
            'status' => 'active', // Define como ativa por padrão
        ]);

        // Retorna a chave recém-criada para o frontend poder adicioná-la à lista dinamicamente
        return response()->json($pixKey, 201); // 201 Created
    }

    /**
     * Remove (soft delete) uma chave PIX específica.
     */
    public function destroy(PixKey $pixKey)
    {
        // Autoriza a ação usando a policy (garante que o usuário só pode deletar suas próprias chaves)
        $this->authorize('delete', $pixKey);

        $pixKey->delete(); // Executa o soft delete

        return response()->json(['message' => 'PIX key removed successfully.']);
    }
}
