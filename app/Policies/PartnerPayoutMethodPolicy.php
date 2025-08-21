<?php

namespace App\Policies;

use App\Models\PartnerPayoutMethod;
use App\Models\User;

class PartnerPayoutMethodPolicy
{
    /**
     * Permite tudo para admins (tratado globalmente no AuthServiceProvider).
     */

    /**
     * Determina se o usuário pode criar um método de pagamento para si mesmo.
     */
    public function create(User $actor, User $partnerToActOn): bool
    {
        // A permissão é concedida se o ator for um admin,
        // OU se o ator for o próprio sócio (adicionando para si mesmo).
        return $actor->isAdmin() || $actor->id === $partnerToActOn->id;
    }

    /**
     * Determina se o usuário pode atualizar o método de pagamento.
     */
    public function update(User $user, PartnerPayoutMethod $payoutMethod): bool
    {
        return $user->id === $payoutMethod->partner_id;
    }

    /**
     * Determina se o usuário pode deletar o método de pagamento.
     */
    public function delete(User $user, PartnerPayoutMethod $payoutMethod): bool
    {
        return $user->id === $payoutMethod->partner_id;
    }
}
