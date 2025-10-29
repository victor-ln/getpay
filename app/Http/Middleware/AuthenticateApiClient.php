<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;

class AuthenticateApiClient
{
    /**
     * Handle an incoming request.
     * Authenticates the API client using Client ID and Secret.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Tenta obter as credenciais do cabeçalho Authorization Basic
        $clientId = $request->getUser();
        $clientSecret = $request->getPassword();

        // Alternativa: Se preferir usar cabeçalhos customizados (ex: X-Client-ID, X-Secret)
        // $clientId = $request->header('X-Client-ID');
        // $clientSecret = $request->header('X-Secret');

        if (!$clientId || !$clientSecret) {
            return response()->json(['message' => 'API credentials missing.'], 401);
        }

        // Busca a conta pelo Client ID
        $account = Account::where('api_client_id', $clientId)->first();

        // Verifica se a conta existe e se o Secret corresponde (usando Hash::check)
        if (!$account || !$account->api_client_secret || !Hash::check($clientSecret, $account->api_client_secret)) {
            return response()->json(['message' => 'Invalid API credentials.'], 401);
        }

        // ✅ Sucesso! Autenticado.
        // Podemos adicionar a conta autenticada à própria requisição
        // para que o controller a possa aceder facilmente.
        $request->attributes->add(['authenticated_account' => $account]);

        return $next($request);
    }
}
