<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // Adicionamos o novo nível de Sócio (Partner)
    const LEVEL_ADMIN = 'admin';
    const LEVEL_CLIENT = 'client';
    const LEVEL_PARTNER = 'partner';

    protected $fillable = [
        'name',
        'email',
        'password',
        'level', // 'admin', 'client', 'partner'
        'status',
        'two_factor_enabled',
        'min_transaction_value',
        'document',
        'referred_by_id', // Coluna que indica qual sócio indicou este usuário/cliente
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * A relação principal do novo modelo.
     * Define a quais Contas (Accounts) este usuário pertence.
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Retorna os pagamentos que este usuário ESPECIFICAMENTE INICIOU.
     * Usado para fins de auditoria. A relação financeira principal estará em Account::class.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Verifica se o usuário é um Administrador.
     */
    public function isAdmin(): bool
    {
        return $this->level === self::LEVEL_ADMIN;
    }

    /**
     * Verifica se o usuário é do tipo Cliente.
     */
    public function isClient(): bool
    {
        return $this->level === self::LEVEL_CLIENT;
    }

    /**
     * Verifica se o usuário é um Sócio/Parceiro.
     */
    public function isPartner(): bool
    {
        return $this->level === self::LEVEL_PARTNER;
    }

    public function sharedProfitAccounts()
    {
        // Define a relação Muitos-para-Muitos com o model Account
        return $this->belongsToMany(Account::class, 'account_partner_commission', 'partner_id', 'account_id')
            // Informa ao Eloquent para também buscar as colunas extras da tabela pivot
            ->withPivot('commission_rate', 'platform_withdrawal_fee_rate')
            ->withTimestamps();
    }

    public function payoutMethods()
    {
        return $this->hasMany(PartnerPayoutMethod::class, 'partner_id');
    }

    public function getMaskedDocumentAttribute()
    {
        $clean = preg_replace('/\D/', '', $this->document);

        if (strlen($clean) == 11) {
            return '***.' . substr($clean, 3, 3) . '.' . substr($clean, 6, 3) . '-**';
        }

        return str_repeat('*', strlen($clean) - 4) . substr($clean, -4);
    }

    public function fees()
    {
        return $this->belongsToMany(Fee::class, 'user_fees')
            ->withPivot('is_default', 'type')
            ->withTimestamps()
            ->latest('user_fees.created_at');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function getRoleInAccount(Account $account): ?string
    {
        // Busca a relação apenas para esta conta específica
        $accountRelation = $this->accounts()->where('account_id', $account->id)->first();

        // Retorna o valor da coluna 'role' da tabela pivot
        return $accountRelation?->pivot->role;
    }
}
