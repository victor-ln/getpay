<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExternalApiClient
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.dubai.url');
        $this->apiKey = config('services.external_api.key');
    }

    /**
     * Faz login na API externa e retorna um token de autenticação.
     */
    public function login()
    {
        $pass = config('services.dubai.password');
        $user = config('services.dubai.login');



        $response = Http::withHeaders([
            'accept' => '*/*',
            'Content-Type' => 'application/json',
        ])->post('https://api.dubai-cash.com/v1/customers/auth/sign-in', [
            'username' => $user,
            'password' => $pass
        ]);



        if ($response->successful()) {
            return $response->json()['access_token']; // Retorna o token da API externa
        }

        throw new \Exception('Erro ao autenticar na API externa.');
    }

    /**
     * Cria uma cobrança na API externa via Pix.
     */
    public function createCharge($data, $token)
    {
        $response = Http::withToken($token)->post("https://api.dubai-cash.com/v1/customers/pix/create-immediate-qrcode", [
            'externalId' => $data['externalId'],
            'amount' => $data['amount'],
            'document' => $data['document'],
            'name' => $data['name'],
            'identification' => $data['identification'] ?? '',
            'expire' => 3600,
            'description' => $data['description']
        ]);





        if ($response['statusCode'] == 200) {
            return $response->json(); // Retorna os dados da cobrança criada
        }

        throw new \Exception('Erro ao criar cobrança.');
    }

    public function createVerification($externalId, $token)
    {
        $response = Http::withToken($token)->get("https://api.dubai-cash.com/v1/customers/pix/status-invoice", [
            'externalId' => $externalId,
        ]);



        if ($response->successful()) {
            return $response->json(); // Retorna os dados da cobrança criada
        }

        throw new \Exception('Erro ao verificar transação.');
    }

    public function createWithdrawCharge($data, $token)
    {


        $response = Http::withToken($token)->post("https://api.dubai-cash.com/v1/customers/pix/withdraw", [
            'externalId' => $data['externalId'],
            'key' => $data['key'],
            'name' => $data['name'],
            'documentNumber' => $data['documentNumber'],
            'description' => $data['description'],
            'bank' => $data['bank'],
            'branch' => $data['branch'],
            'account' => $data['account'],
            'amount' => $data['amount'],
            'memo' => $data['memo']
        ]);





        if ($response['statusCode'] == 200) {
            return $response->json(); // Retorna os dados da cobrança criada
        }

        throw new \Exception('Erro ao criar cobrança.');
    }
}
