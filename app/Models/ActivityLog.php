<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'action',
        'level',
        'message',
        'context',
        'ip_address',
        'user_agent'
    ];

    // Converte o campo de contexto para array/objeto automaticamente
    protected $casts = ['context' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
