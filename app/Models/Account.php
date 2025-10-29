<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Bank;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'partner_id',
        'status',
        'min_amount_transaction',
        'max_amount_transaction',
        'acquirer_id',
    ];




    /**
     * O sócio (partner) que indicou esta conta.
     */
    public function partner()
    {
        // Note que a relação é com o model User, pois o sócio é um usuário.
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function fees()
    {
        return $this->belongsToMany(Fee::class, 'account_fees')
            ->withPivot('type', 'status', 'created_at', 'updated_at')
            ->withTimestamps()
            ->orderByPivot('id', 'desc');
    }

    /**
     * Os webhooks associados a esta conta.
     * Assumindo que a tabela 'webhooks' terá um campo 'account_id'.
     */
    public function webhooks()
    {
        return $this->hasMany(Webhook::class, 'account_id');
    }

    /**
     * As chaves PIX associadas a esta conta.
     * Assumindo que a tabela 'pix_keys' terá um campo 'account_id'.
     */
    public function pixKeys()
    {
        return $this->hasMany(AccountPixKey::class);
    }



    public function profitSharingPartners()
    {
        // O modelo relacionado é User, pois um Sócio é um Usuário.
        return $this->belongsToMany(User::class, 'account_partner_commission', 'account_id', 'partner_id')
            // Também informa para buscar as colunas extras
            ->withPivot('commission_rate', 'platform_withdrawal_fee_rate')
            ->withTimestamps();
    }

    public function feeProfiles()
    {
        // Uma conta pode ter vários perfis, um para cada tipo de transação
        return $this->belongsToMany(FeeProfile::class, 'account_fee_profile')
            ->withPivot('transaction_type', 'status') // Importante para podermos acessar a coluna extra
            ->withTimestamps();
    }

    public function acquirer()
    {
        return $this->belongsTo(Bank::class, 'acquirer_id');
    }


    public function payments(): HasMany
    {
        // Esta linha diz ao Laravel: "Uma Conta tem muitos Payments,
        // onde a coluna 'account_id' na tabela 'payments'
        // corresponde ao 'id' desta conta."
        return $this->hasMany(Payment::class);
    }

    public function balances()
    {
        return $this->hasMany(Balance::class, 'account_id');
    }

    public function getTotalAvailableBalanceAttribute(): float
    {

        return $this->balances()
            ->whereHas('bank', function ($query) {

                $query->where('active', true);
            })

            ->sum('available_balance');
    }


    public function getCurrentAcquirerBalance(): ?Balance
    {

        return $this->balances()->where('acquirer_id', $this->acquirer_id)->first();
    }


    public function users()
    {
        return $this->belongsToMany(User::class, 'account_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}
