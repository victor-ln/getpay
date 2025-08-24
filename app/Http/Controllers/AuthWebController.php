<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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




            if ($user->isAdmin()) {
                // Redireciona para o dashboard de sócios que criamos
                return redirect()->route('partner.dashboard');
            }
            return redirect()->intended('dashboard');
        }

        event(new \App\Events\UserActionOccurred(null, 'LOGIN_FAILED', ['email' => $credentials['email']], 'Failed login attempt.'));

        return back()->withErrors([
            'email' => 'The credentials you provided do not match our records.',
        ]);
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect('/login')->with('success', 'Conta criada com sucesso! Faça login.');
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
