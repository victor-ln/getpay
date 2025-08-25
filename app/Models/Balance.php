<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Balance extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'acquirer_id',
        'available_balance',
        'blocked_balance',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function bank()
    {
        // Lembre-se de usar o nome correto do seu model de Adquirente (Acquirer, Bank, etc.)
        return $this->belongsTo(Bank::class, 'acquirer_id');
    }
}
