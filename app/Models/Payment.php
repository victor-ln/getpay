<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bank;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'user_id',
        'external_payment_id',
        'amount',
        'fee',
        'cost',
        'type_transaction',
        'status',
        'provider_id',
        'provider_transaction_id',
        'description',
        'name',
        'document',
    ];



    /**
     * Get the user that owns the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the fee that was applied to this payment.
     */
    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }

    public function getStatusClassAttribute(): string
    {
        if ($this->status == 'pending') {
            return 'text-warning';
        }

        if ($this->status == 'paid') {
            return $this->type_transaction == 'IN' ? 'text-success' : 'text-danger';
        }

        return '';
    }

    public function getSignedAmountAttribute(): string
    {
        $sign = $this->type_transaction == 'IN' ? '+' : '-';
        // Formata o valor e concatena o span BRL
        $formattedAmount = number_format($this->amount, 2, ',', '.');
        return "{$sign} {$formattedAmount} <span class=\"text-muted\">BRL</span>";
    }

    public function provider()
    {
        // 'provider_id' é a chave estrangeira na tabela 'payments'
        // 'id' é a chave primária na tabela 'banks'
        return $this->belongsTo(Bank::class, 'provider_id', 'id');
    }

    public function account()
    {
        // O Laravel irá procurar pela chave estrangeira 'account_id' na tabela 'payments'
        // para fazer a ligação com a tabela 'accounts'.
        return $this->belongsTo(Account::class, 'account_id');
    }
}
