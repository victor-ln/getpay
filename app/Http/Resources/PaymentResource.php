<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Esta é a lista de campos que SERÃO exibidos na sua API.
        // Os campos 'provider_id', 'cost', e 'platform_profit' foram omitidos.
        return [
            'id' => $this->id,
            'provider_transaction_id' => $this->provider_transaction_id,
            'external_payment_id' => $this->external_payment_id,
            'amount' => (float) $this->amount, // Boa prática fazer o cast para float
            'fee' => (float) $this->fee,
            'type_transaction' => $this->type_transaction,
            'status' => $this->status,
            'end_to_end_id' => $this->end_to_end_id,
            'identification' => $this->identification,
            'description' => $this->description,
            'document' => $this->document,
            'name' => $this->name,
            'created_at' => $this->created_at->toIso8601String(), // Formato padrão para APIs
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
