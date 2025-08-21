<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeTier extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'fee_profile_id',
        'min_value',
        'max_value',
        'fixed_fee',
        'percentage_fee',
        'priority',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FeeProfile::class);
    }
}
