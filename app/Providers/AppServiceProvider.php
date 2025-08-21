<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\View\Composers\MenuComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        \Log::info('REDIS_PORT value: ' . env('REDIS_PORT'));
        \Log::info('REDIS_PORT type: ' . gettype(env('REDIS_PORT')));
        \Log::info('Config redis port: ' . config('database.redis.default.port'));


        if ($this->app->environment('production')) { // Ou use $this->app->environment('production')
            URL::forceScheme('https');
        }
        Paginator::useBootstrapFive();
        View::composer('*', MenuComposer::class);

        Carbon::setLocale('pt_BR');
        date_default_timezone_set('America/Sao_Paulo');
    }

    protected function getMenuData()
    {
        $jsonPath = resource_path('menu/verticalMenu.json');
        if (!file_exists($jsonPath)) {
            abort(500, 'Arquivo verticalMenu.json não encontrado.');
        }
        $menuData = json_decode(file_get_contents($jsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(500, 'Erro ao decodificar verticalMenu.json: ' . json_last_error_msg());
        }
        return $menuData;
    }
}
