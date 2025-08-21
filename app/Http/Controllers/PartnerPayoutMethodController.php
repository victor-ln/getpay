<?php

namespace App\Http\Controllers;

use App\Models\PartnerPayoutMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PartnerPayoutMethodController extends Controller
{
    public function store(Request $request)
    {
        // O usuário que está realizando a ação (o admin logado)
        $actor = Auth::user();

        // Valida os dados, incluindo o ID do sócio alvo
        $validated = $request->validate([
            'partner_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'string', Rule::in(['CPF', 'EMAIL', 'PHONE', 'EVP', 'CNPJ'])],
            'key'  => ['required', 'string', 'max:255', Rule::unique('partner_payout_methods', 'pix_key')->where('partner_id', $request->input('partner_id'))],
        ]);

        // Encontra o sócio para quem a chave será criada
        $partner = User::find($validated['partner_id']);

        // Autoriza a ação: o 'ator' pode criar uma chave para o 'sócio alvo'?
        $this->authorize('create', [PartnerPayoutMethod::class, $partner]);

        // Se a validação e autorização passarem, cria a chave para o sócio correto
        $is_default = $partner->payoutMethods()->count() === 0;

        $payoutMethod = $partner->payoutMethods()->create([
            'pix_key_type' => $validated['type'],
            'pix_key' => $validated['key'],
            'is_default' => $is_default
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PIX key added successfully for ' . $partner->name,
            'html' => view('users.partials.payout-method-row', ['payoutMethod' => $payoutMethod])->render()
        ]);
    }

    public function destroy(PartnerPayoutMethod $payoutMethod)
    {
        $this->authorize('delete', $payoutMethod);

        if ($payoutMethod->is_default) {
            return response()->json(['message' => 'You cannot delete the default payout method.'], 422);
        }

        $payoutMethod->delete();

        return response()->json(['message' => 'Payout method removed successfully.']);
    }

    public function setDefault(PartnerPayoutMethod $payoutMethod)
    {
        $this->authorize('update', $payoutMethod);

        // Remove o status 'default' de todos os outros métodos deste parceiro
        $payoutMethod->partner->payoutMethods()->update(['is_default' => false]);

        // Define o método atual como o padrão
        $payoutMethod->update(['is_default' => true]);

        return back()->with('success', 'Default payout method updated successfully.');
    }
}
