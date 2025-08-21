<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'percentage',
        'minimum_fee',
        'fixed_fee',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users associated with this fee.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_fees')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::deleted(function ($fee) {
            $fee->userFees()->update(['status' => '0']);
        });
    }

    public function userFees()
    {
        return $this->hasMany(UserFee::class, 'fee_id');
    }

    public function accountFees()
    {
        return $this->hasMany(AccountFee::class, 'fee_id');
    }

    /**
     * Get the payments that use this fee.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
