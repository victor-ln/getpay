<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(400)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('logins', function (Request $request) {
            // Limita a 5 tentativas por minuto, identificado pelo email E pelo IP.
            return Limit::perMinute(120)->by($request->input('email') . '|' . $request->ip());
        });

        RateLimiter::for('financials', function (Request $request) {
            // Limita a 10 requisições por minuto por usuário.
            // Este é um bom ponto de partida para transações financeiras.
            return Limit::perMinute(120)->by($request->user()->id);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
