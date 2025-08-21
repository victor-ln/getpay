<?php

namespace App\Providers;

use App\Models\AccountPixKey;
use App\Models\PartnerPayoutMethod;
use App\Models\User;
use App\Models\Webhook;
use App\Policies\PartnerPayoutMethodPolicy;
use App\Policies\PixKeyPolicy;
use App\Policies\UserPolicy;
use App\Policies\WebhookPolicy;
use Illuminate\Support\Facades\Gate; // ADICIONE ESTA LINHA

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
        User::class => UserPolicy::class,
        AccountPixKey::class => PixKeyPolicy::class,
        Webhook::class => WebhookPolicy::class,
        PartnerPayoutMethod::class => PartnerPayoutMethodPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            // Se o método isAdmin() do usuário retornar true, ele tem permissão para TUDO.
            if ($user->isAdmin()) {
                return true;
            }
        });
    }
}
