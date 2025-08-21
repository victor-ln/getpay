<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserFee extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'user_fees';
    protected $fillable = [
        'user_id',
        'fee_id',
        'is_default',
        'type',
        'status'
    ];

    public function deactivatePreviousFees()
    {
        // Desativa taxas anteriores do mesmo tipo para o usuário
        $this->where('user_id', $this->user_id)
            ->where('type', $this->type)
            ->where('status', '1')
            ->update(['status' => '0']);
    }

    // Chame o método deactivatePreviousFees() no evento creating ou created
    protected static function booted()
    {
        static::creating(function ($userFee) {
            $userFee->deactivatePreviousFees();
        });
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class, 'fee_id');
    }
}
