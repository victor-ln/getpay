<?php

namespace App\Services\Acquirers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LumenAcquirerService implements AcquirerInterface
{
    protected $baseUrl;
    protected $client;
    protected $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.lumenPay.url');
        $this->client = config('services.lumenPay.client');
        $this->secret = config('services.lumenPay.secret');
    }

    public function getToken()
    {
        try {
            $response = Http::withHeaders([
                'accept' => '*/*',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'GerarToken', [
                'client_id' => $this->client,
                'client_secret' => $this->secret
            ]);

            if ($response->successful()) {
                return $response->json()['jwt'];
            }

            Log::error('Lumen authentication error', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Lumen authentication exception', [
                'message' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    public function createCharge(array $data, string $token)
    {
        try {
            // Convert amount to cents for Lumen
            $amountInCents = (int) ($data['AMOUNT'] * 100);
            
            $response = Http::withToken($token)->post($this->baseUrl . "RegisterPixAccount", [
                'client_id' => $this->client,
                'client_secret' => $this->secret,
                'value_cents' => $amountInCents,
                'generator_name' => $data['NAME'],
                'generator_document' => $data['DOCUMENT'],
                'expiration_time' => $data['EXPIRE'] ?? 3600,
                'external_reference' => $data['EXTERNALID']
            ]);

            return [
                'statusCode' => $response->status(),
                'data' => $response->json(),
                'acquirer' => 'lumen'
            ];
        } catch (\Exception $e) {
            Log::error('Lumen create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'lumen'
            ];
        }
    }
}