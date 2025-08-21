<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountFee extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'account_fees';
    protected $fillable = [
        'account_id',
        'fee_id',
        'type',
        'status'
    ];



    public function deactivatePreviousFees()
    {
        // Desativa taxas anteriores do mesmo tipo para a conta
        static::where('account_id', $this->account_id)
            ->where('type', $this->type)
            ->where('status', '1')
            ->where('id', '!=', $this->id ?? 0) // Evita desativar a própria taxa
            ->update(['status' => '0']);
    }

    // Use o evento creating ou created
    protected static function booted()
    {
        // static::creating(function ($accountFee) {
        //     $accountFee->deactivatePreviousFees();
        // });


        static::created(function ($accountFee) {
            $accountFee->deactivatePreviousFees();
        });
    }

    public function feeRule()
    {
        return $this->belongsTo(Fee::class, 'fee_id');
    }
}
