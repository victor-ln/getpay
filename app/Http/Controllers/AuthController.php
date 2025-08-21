<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required',
    //         'email' => 'required|email|unique:users',
    //         'password' => 'required|min:6'
    //     ]);

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password)
    //     ]);

    //     return response()->json(['token' => $user->createToken('API Token')->plainTextToken]);
    // }

    public function login(Request $request)
    {
        // 1. Validação dos campos de entrada
        // Se esta validação falhar e for uma chamada de API (rota em api.php ou header Accept: application/json),
        // o Laravel já retornará uma resposta JSON com status 422.
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 2. Tentativa de encontrar o usuário
        $user = User::where('email', $request->email)->first();

        // 3. Verificar se o usuário existe e se a senha está correta
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Em vez de lançar ValidationException, retorne uma resposta JSON diretamente
            $resp = array(
                'success' => false,
                'message' => 'Bad credentials',
            );

            return json_encode($resp);
        }

        if ($user->status == 0) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact your manager.'
            ], 403);
        }



        // 4. Se as credenciais estiverem corretas, gerar o token
        $expiresAt = now()->addHour(); // Define a expiração do token (1 hora)

        // Crie o token. O segundo argumento são as 'abilities' ou 'scopes' do token.
        $tokenResult = $user->createToken('auth_token', ['payments'], $expiresAt);
        $token = $tokenResult->plainTextToken;

        // 5. Retornar a resposta de sucesso com o token
        $resp = array(
            'success' => true,
            'message' => 'Token generated successfully!', // Mensagem de sucesso
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(), // Ou ->toIso8601String() para um formato padrão
            // 'expires_in' => $expiresAt->diffInSeconds(now()), // Segundos até a expiração
        );

        return json_encode($resp);
    }
}
