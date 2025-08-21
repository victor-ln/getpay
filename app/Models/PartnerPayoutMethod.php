<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerPayoutMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'pix_key_type',
        'pix_key',
        'is_default',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
