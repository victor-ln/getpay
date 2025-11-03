<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPartnerCommission extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada ao model.
     * (Necessário se o nome da tabela for singular)
     */
    protected $table = 'account_partner_commission';

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'account_id', // O ID da conta do cliente
        'partner_id', // O ID do User (Sócio)
        'commission_rate', // A % de comissão (ex: 50.00 para 50%)
        'platform_withdrawal_fee_rate', // A sua outra coluna de taxa
        'min_fee_for_commission', // A taxa mínima para comissão
    ];

    /**
     * Define os casts para os tipos de dados.
     */
    protected $casts = [
        'commission_rate' => 'decimal:2',
        'platform_withdrawal_fee_rate' => 'decimal:2',
        'min_fee_for_commission' => 'decimal:2',
    ];

    // Não precisa de timestamps (created_at, updated_at)
    public $timestamps = false;
}
