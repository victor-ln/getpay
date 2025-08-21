<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookPolicy
{
    use HandlesAuthorization;

    /**
     * Determina se o usuário pode criar webhooks para uma determinada conta.
     * Este método é chamado quando você usa $this->authorize('create', [Webhook::class, $account]);
     */
    public function create(User $user, Account $account): bool
    {
        // A regra do admin já é tratada globalmente pelo Gate::before.
        // A permissão é concedida se o usuário for membro da conta.
        return $user->accounts()->whereKey($account->id)->exists();
    }

    /**
     * Determina se o usuário pode atualizar o webhook.
     */
    public function update(User $user, Webhook $webhook): bool
    {
        // A regra do admin já é tratada globalmente pelo Gate::before.
        // A permissão é concedida se o usuário for membro da conta à qual o webhook pertence.
        return $user->accounts()->whereKey($webhook->account_id)->exists();
    }

    /**
     * Determina se o usuário pode deletar o webhook.
     */
    public function delete(User $user, Webhook $webhook): bool
    {
        // A lógica para deletar é a mesma que para atualizar.
        return $this->update($user, $webhook);
    }
}
