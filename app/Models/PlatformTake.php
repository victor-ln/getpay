<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformTake extends Model
{
    use HasFactory;

    /**
     * O nome da tabela. O Laravel geralmente adivinha, mas é bom ser explícito.
     * @var string
     */
    protected $table = 'platform_takes';

    /**
     * Os atributos que podem ser preenchidos em massa.
     * @var array
     */
    protected $fillable = [
        'total_profit',
        'report_data',
        'start_date',
        'end_date',
        'source_bank_id',
        'destination_payout_key_id',
        'executed_by_user_id',
        'payout_status',
        'payout_provider_transaction_id',
        'payout_failure_reason',
    ];

    /**
     * Define os tipos de dados de atributos específicos.
     * @var array
     */
    protected $casts = [
        'report_data' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Relação: O usuário que executou o Take.
     */
    public function executedBy()
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    /**
     * Relação: O banco de origem do dinheiro.
     */
    public function sourceBank()
    {
        return $this->belongsTo(Bank::class, 'source_bank_id');
    }

    /**
     * Relação: O destino do dinheiro.
     */
    public function destination()
    {
        return $this->belongsTo(PayoutDestination::class, 'destination_payout_key_id');
    }
}