<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LumenApiClient
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

    /**
     * Faz login na API externa e retorna um token de autenticação.
     */
    public function login()
    {

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

        throw new \Exception('Erro ao autenticar na API externa.');
    }

    /**
     * Cria uma cobrança na API externa via Pix.
     */
    public function createCharge($data, $token)
    {
        $response = Http::withToken($token)->post($this->baseUrl . "RegisterPixAccount", [
            'client_id' => $this->client,
            'client_secret' => $this->secret,
            'value_cents' => $data['amount'],
            'generator_name' => $data['name'],
            'generator_document' => $data['document'],
            'expiration_time' => 3600,
            'external_reference' => $data['externalId']
        ]);

        $statusCode = $response->status();

        if ($statusCode == 200) {
            return $response->json();
        }



        throw new \Exception('Erro ao criar cobrança.');
    }





    public function getSaldo($token)
    {

        $response = Http::withToken($token)->withHeaders([
            'Content-Type' => 'application/json',
        ])->send('GET', $this->baseUrl . 'GetBalance', [
            'json' => [
                'client_id' => $this->client,
                'client_secret' => $this->secret,
            ],
        ]);

        return $response->json();
    }
}
