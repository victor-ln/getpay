<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'calculation_type',
        'fixed_fee',
        'base_fee',
        'percentage_fee',
    ];


    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_fee_profile')
            ->withPivot('transaction_type')
            ->withTimestamps();
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(FeeTier::class);
    }
}
