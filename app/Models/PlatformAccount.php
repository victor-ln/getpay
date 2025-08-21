<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlatformAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'bank_id',
        'account_name',
        'current_balance',
    ];

    /**
     * Define a relação: Uma conta da plataforma pertence a um Banco/Adquirente.
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
