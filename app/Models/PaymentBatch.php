<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentBatch extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'acquirer_id',
        'total_amount',
        'number_of_splits',
        'status',
    ];

    /**
     * Pega o usuário (admin) que criou este lote.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pega a liquidante associada a este lote.
     * (Ajuste 'Acquirer::class' se o seu model tiver um nome diferente)
     */
    public function acquirer(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Pega todas as transações (filhas) que pertencem a este lote.
     * (Ajuste 'Payment::class' se o seu model tiver um nome diferente)
     */
    public function transactions(): HasMany
    {
        // Nota: O Laravel usa 'transactions' como nome da função,
        // mas aponta para o Model 'Payment'.
        return $this->hasMany(Payment::class);
    }
}