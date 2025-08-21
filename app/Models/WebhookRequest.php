<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'webhook_requests';

    protected $fillable = [
        'ip_address',
        'payload',
        'signature',
        'user_id',
    ];
}
