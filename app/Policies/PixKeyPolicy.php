<?php

namespace App\Policies;

use App\Models\AccountPixKey;
use App\Models\PixKey;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PixKeyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PixKey $pixKey): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Por padrão, qualquer usuário autenticado pode tentar criar uma chave para si mesmo.
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AccountPixKey $pixKey): bool
    {
        // 1. Se o usuário for um admin, ele tem permissão total.
        if ($user->isAdmin()) {
            return true;
        }

        // 2. Verifica se o usuário pertence à conta dona da chave PIX
        return $user->accounts()->whereKey($pixKey->account_id)->exists();
    }
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PixKey $pixKey): bool
    {
        //
    }



    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PixKey $pixKey): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PixKey $pixKey): bool
    {
        //
    }
}
