<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'token',
        'user',
        'password',
        'client_id',
        'client_secret',
        'config',
        'baseurl',
        'active',
        'fees_config',
        'api_config',
    ];

    protected $casts = [
        'active' => 'boolean',
        'fees_config' => 'array',
        'api_config' => 'array',
    ];

    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    public function getPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setClientSecretAttribute($value)
    {
        if ($value) {
            $this->attributes['client_secret'] = Crypt::encryptString($value);
        }
    }

    public function getClientSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'provider_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'acquirer_id');
    }
}
