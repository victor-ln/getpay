<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthWebController extends Controller
{
    public function loginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('dashboard');
        }
        return view('auth.login');
    }

    // Processar login
    public function login(Request $request)
    {


        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            event(new \App\Events\UserActionOccurred(Auth::user(), 'LOGIN_SUCCESS', [], 'User logged in successfully.'));

            $user = Auth::user();

            if ($user->status == 0) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is inactive. Please contact your manager.',
                ]);
            }




            // if ($user->isAdmin()) {
            //     // Redireciona para o dashboard de sócios que criamos
            //     return redirect()->route('partner.dashboard');
            // }


            return redirect()->intended('dashboard');
        }

        event(new \App\Events\UserActionOccurred(null, 'LOGIN_FAILED', ['email' => $credentials['email']], 'Failed login attempt.'));

        return back()->withErrors([
            'email' => 'The credentials you provided do not match our records.',
        ]);
    }

    public function registerForm(Request $request) // ✅ Aceita a Request
    {
        $referralCode = null;
        $affiliateName = null;

        // Verifica se um código de referência (ref) está presente na URL
        if ($request->has('ref')) {
            $code = $request->query('ref');
            // Procura a conta que possui este código
            $affiliateAccount = Account::where('referral_code', $code)->first();

            if ($affiliateAccount) {
                // Se o código for válido, guarda o código e o nome para a view
                $referralCode = $code;
                $affiliateName = $affiliateAccount->name;
            }
        }

        // Retorna a view de registo com os dados (ou nulos)
        return view('auth.register', [
            'referral_code' => $referralCode,
            'affiliate_name' => $affiliateName,
        ]);
    }

    public function register(Request $request)
    {
        // 1. Validação (Atualizada)
        // Valida os campos do Utilizador E os novos campos da Conta
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'account_name' => 'required|string|max:255', // Campo para o nome da nova conta
            'referral_code' => 'nullable|string|exists:accounts,referral_code', // Valida o código, se existir
        ]);

        // 2. Lógica de Criação (Agora dentro de uma Transação Segura)
        try {
            DB::transaction(function () use ($validatedData, $request) {

                // 2a. Lógica do Afiliado
                $referred_by_account_id = null;
                if ($request->filled('referral_code')) {
                    // A validação 'exists' já garante que o código é válido
                    $affiliateAccount = Account::where('referral_code', $validatedData['referral_code'])->first();
                    if ($affiliateAccount) {
                        $referred_by_account_id = $affiliateAccount->id;
                    }
                }

                // 2b. Criar a Conta (INATIVA, como pedido)
                $newAccount = Account::create([
                    'name' => $validatedData['account_name'],
                    'referral_code' => 'ACC_' . Str::random(10), // Gera um código único para esta *nova* conta
                    'referred_by_account_id' => $referred_by_account_id, // Guarda quem a indicou
                    'status' => 0,
                ]);

                // 2c. Criar o Utilizador
                $newUser = User::create([
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'password' => Hash::make($validatedData['password']),
                    'level' => 'client', // Define o nível padrão
                    'status' => 0, // Define o estado como INATIVO
                ]);

                // 2d. Ligar o novo Utilizador à nova Conta (com o papel 'owner')
                // Isto assume que a sua tabela pivot 'account_user' existe
                $newUser->accounts()->attach($newAccount->id, ['role' => 'owner']);
            });
        } catch (\Exception $e) {
            // Se algo falhar (ex: erro na base de dados), regista o erro e devolve o utilizador
            Log::error('Falha no registo de nova conta/utilizador: ' . $e->getMessage());
            return back()->withInput()->with('error', 'An error occurred during registration. Please try again.');
        }

        // 3. Resposta de Sucesso (Atualizada)
        // Informa o utilizador que a conta precisa de ser aprovada
        return redirect('/login')->with('success', 'Account created successfully! Please wait for admin approval to log in.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        event(new \App\Events\UserActionOccurred(null, 'LOGOUT', [], 'User logged out successfully.'));

        return redirect('/login');
    }
}
