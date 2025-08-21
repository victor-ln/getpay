<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'webhooks';

    protected $fillable = ['account_id', 'url', 'event', 'secret_token', 'is_active', 'user_id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
