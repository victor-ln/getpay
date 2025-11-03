<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Regra "mestra": Admins podem fazer tudo.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null; // Deixa as outras regras decidirem
    }

    /**
     * Determina se o utilizador pode ver a lista de produtos (o index).
     */
    public function viewAny(User $user): bool
    {
        // Qualquer utilizador logado que tenha uma conta selecionada pode ver a lista
        return session()->has('selected_account_id');
    }

    /**
     * Determina se o utilizador pode ver um produto específico.
     * (Não estamos a usar no 'show', mas é boa prática ter)
     */
    public function view(User $user, Product $product): bool
    {
        // O utilizador pode ver o produto SE o produto pertencer à conta selecionada
        return $product->account_id === session('selected_account_id');
    }

    /**
     * Determina se o utilizador pode criar produtos.
     */
    public function create(User $user): bool
    {
        // Qualquer utilizador logado que tenha uma conta selecionada pode criar
        return session()->has('selected_account_id');
    }

    /**
     * Determina se o utilizador pode atualizar o produto.
     * (Usado nos métodos 'edit' e 'update' do controller)
     */
    public function update(User $user, Product $product): bool
    {
        // O utilizador pode atualizar o produto SE o produto pertencer à conta selecionada
        return $product->account_id === session('selected_account_id');
    }

    /**
     * Determina se o utilizador pode apagar o produto.
     */
    public function delete(User $user, Product $product): bool
    {
        // O utilizador pode apagar o produto SE o produto pertencer à conta selecionada
        return $product->account_id === session('selected_account_id');
    }
}
