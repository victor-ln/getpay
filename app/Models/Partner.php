<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'pix_key',
        'pix_key_type',
        'receiving_percentage',
        'withdrawal_frequency',
        'custom_withdrawal_days',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'receiving_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'custom_withdrawal_days' => 'integer',
    ];

    // Você pode adicionar aqui relationships ou outros métodos do model no futuro
}
