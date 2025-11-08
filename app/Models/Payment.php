<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bank;
use App\Models\PaymentBatch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'user_id',
        'external_payment_id',
        'amount',
        'fee',
        'cost',
        'type_transaction',
        'status',
        'provider_id',
        'provider_transaction_id',
        'description',
        'name',
        'document',
        'provider_response_data',
        'payment_batch_id',
        'description',
    ];

    protected $casts = [
        'provider_response_data' => 'array',
    ];


    /**
     * Get the user that owns the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the fee that was applied to this payment.
     */
    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }

    public function getStatusClassAttribute(): string
    {
        if ($this->status == 'pending') {
            return 'text-warning';
        }

        if ($this->status == 'paid') {
            return $this->type_transaction == 'IN' ? 'text-success' : 'text-danger';
        }

        return '';
    }

    public function getSignedAmountAttribute(): string
    {
        $sign = $this->type_transaction == 'IN' ? '+' : '-';
        
        $formattedAmount = number_format($this->amount, 2, ',', '.');
        return "{$sign} {$formattedAmount} <span class=\"text-muted\">BRL</span>";
    }

    public function provider()
    {
        
        
        return $this->belongsTo(Bank::class, 'provider_id', 'id');
    }

    public function account()
    {
        
        
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function bank()
    {
        
        
        
        return $this->belongsTo(Bank::class, 'provider_id');
    }

    public function paymentBatch(): BelongsTo
    {
        return $this->belongsTo(PaymentBatch::class);
    }

   public function acquirer(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Decodifica o provider_response_data. (Este fica igual)
     */
    protected function getResponseDataAttribute(): ?object
    {
        if (empty($this->provider_response_data)) {
            return null;
        }
        return json_decode($this->provider_response_data);
    }

    

    /**
     * Pega a imagem do QR Code (Base64) da resposta.
     */
    public function getQrCodeImageAttribute(): ?string
    {
        $response = $this->responseData;
        if (!$response) {
            return null;
        }

        $acquirerName = $this->acquirer->name ?? 'default';

         return $response->qrcode;

        
    }

    /**
     * Pega o "Copia e Cola" da resposta.
     */
    public function getCopyPasteCodeAttribute(): ?string
    {
        $response = $this->responseData;
        if (!$response) {
            return null;
        }

        $acquirerName = $this->acquirer->name ?? 'default';

        return $response->pix ?? null;

        
    }
}
