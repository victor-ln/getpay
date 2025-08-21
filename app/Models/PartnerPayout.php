<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerPayout extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'partner_id',
        'payment_id',
        'amount',
        'status',
        'processed_at',
        'calculation_details',
        'failure_reason',
    ];

    /**
     * Converte o campo de detalhes para array/objeto automaticamente.
     */
    protected $casts = [
        'calculation_details' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Define a relação: Um Payout pertence a um Sócio (Partner).
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Define a relação: Um Payout está associado a uma Transação (Payment).
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
