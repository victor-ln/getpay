<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceHistory extends Model
{
    use HasFactory;

    // Para uma tabela de log, geralmente não precisamos da coluna 'updated_at'.
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'account_id',
        'acquirer_id',
        'payment_id',
        'type',
        'balance_before',
        'amount',
        'balance_after',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'balance_before' => 'decimal:2',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relacionamentos para facilitar futuras consultas
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    public function bank()
    {
        return $this->belongsTo(Bank::class, 'acquirer_id');
    }
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
