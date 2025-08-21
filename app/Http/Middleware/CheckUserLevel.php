<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserLevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$levels)
    {
        $user = auth()->user();

        if (!$user || !in_array($user->level, $levels)) {
            return redirect()->back()->with('toast', [
                'type' => 'bg-danger',
                'title' => 'Access Denied',
                'message' => 'You do not have permission to access this area.'
            ]);
        }

        return $next($request);
    }
}
