<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledTake extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'frequency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Um agendamento pertence a um Banco.
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Método auxiliar para traduzir a frequência para uma expressão Cron.
     */
    public function getCronExpression(): string
    {
        return match ($this->frequency) {
            'everyTenMinutes' => '*/10 * * * *',
            'everyThirtyMinutes' => '*/30 * * * *',
            'hourly' => '0 * * * *',
            'daily' => '0 0 * * *',
            default => $this->frequency, // Permite expressões Cron customizadas
        };
    }
}
