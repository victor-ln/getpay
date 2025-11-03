<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'price',
        'status',
    ];

    /**
     * Converte automaticamente o preço para o tipo correto.
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Define o relacionamento: um Produto PERTENCE A uma Conta.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
