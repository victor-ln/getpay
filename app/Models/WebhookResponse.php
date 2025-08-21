<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookResponse extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'webhook_responses';

    protected $fillable = [
        'webhook_request_id',
        'status_code',
        'headers',
        'body',
    ];
}
