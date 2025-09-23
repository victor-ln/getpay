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


        $clientIp = $request->header('CF-Connecting-IP') ?? $request->ip();


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

        $allowedIps = [
            '69.162.120.198',
            '2804:214:8678:cb24:1:0:2bdc:7e9e'
        ];

        // [MODIFICADO] Define a lista de usuários com restrição de IP
        $restrictedUserIds = [30, 28, 21, 9];
        if (in_array($user->id, $restrictedUserIds) && !in_array($clientIp, $allowedIps)) {
            // Log com o contexto correto
            $context = [
                'detected_ip_by_logic' => $clientIp,
                'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
                'laravel_default_ip' => $request->ip(),
                'remote_addr' => $request->server('REMOTE_ADDR'),
            ];
            event(new \App\Events\UserActionOccurred($user, 'LOGIN_FAILED_IP_MISMATCH', $context, 'Login attempt from a blocked IP.'));

            // Retorna a resposta de erro com o IP correto
            return response()->json([
                'success' => false,
                'message' => 'Access denied from your IP address: ' . $clientIp
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
