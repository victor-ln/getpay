<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token ausente'], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = PersonalAccessToken::findToken($token);



        if (!$accessToken || ($accessToken->expires_at && now()->greaterThan($accessToken->expires_at))) {
            return response()->json(['message' => 'Token expirado'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
