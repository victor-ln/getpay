<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PixKey extends Model
{
    use HasFactory, SoftDeletes; // Use o SoftDeletes

    /**
     * Os atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'key',
        'status',
    ];

    /**
     * Define a relação de que uma chave PIX pertence a um usuário.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
