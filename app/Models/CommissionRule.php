<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionRule extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'transaction_type',
        'priority',
        'payee_type',
        'partner_id',
        'value_type',
        'value',
    ];

    /**
     * A regra pertence a uma Conta.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * A regra pode pertencer a um Sócio (User).
     */
    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
