<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AffiliateController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Pega na conta que o utilizador está a visualizar
        $selectedAccountId = session('selected_account_id');
        $selectedAccount = Account::find($selectedAccountId);

        if (!$selectedAccount) {
            return redirect()->route('dashboard')->with('error', 'Please select an account first.');
        }

        // Garante que o código de referência existe (cria um se não tiver)
        if (empty($selectedAccount->referral_code)) {
            $selectedAccount->referral_code = 'ACC_' . Str::random(10);
            $selectedAccount->save();
        }

        // Monta o link de referência completo
        $referralLink = route('register') . '?ref=' . $selectedAccount->referral_code; // Ajuste 'register' para a sua rota de registo


        $kpis = [
            'total_referred' => 0, // Lógica futura: Account::where('referred_by_account_id', $selectedAccount->id)->count()
            'referred_volume_month' => 0.00, // Lógica futura
            'commission_month' => 0.00, // Lógica futura
            'commission_today' => 0.00, // Lógica futura
        ];

        $referredAccounts = collect(); // Lógica futura

        return view('affiliate.index', compact('selectedAccount', 'referralLink', 'kpis', 'referredAccounts'));
    }
}
