<?php

namespace App\Services\Acquirers;

use App\Models\Bank;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Acquirers\AcquirerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;



class OwenAcquirerService implements AcquirerInterface
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $credentials;
    protected $accountId;
    protected $userId;


    public function __construct(Bank $bank)
    {
        $this->baseUrl = $this->ensureTrailingSlash($bank->baseurl);
        $this->username = $bank->client_id;
        $this->password = $bank->client_secret;
        $this->accountId = $bank->token;
        $this->userId = $bank->user;
        $this->credentials = base64_encode($this->username . ':' . $this->password);
    }

    public function getToken()
    {

        try {




            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'API Client/1.0',
                'X-Requested-With' => 'XMLHttpRequest',
                'Authorization' => 'Basic ' . $this->credentials,
            ])
                ->withOptions([
                    //  'verify' => false
                ])
                ->get($this->baseUrl . 'ping');

            // Verificar se a requisição foi bem-sucedida
            if ($response->failed()) {
                // Log do erro para debug
                \Log::error('Ping request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null; // ou throw exception
            }

            // Tentar obter o JSON
            $data = $response->json();

            if (is_array($data) && isset($data['success']) && $data['success'] === true) {
                return $data['requestId'] ?? null;
            }



            Log::error('Owen authentication error', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Owen authentication exception', [
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function createCharge(array $data, string $token)
    {




        try {
            $response = Http::withOptions([
                //'verify' => false,
                'allow_redirects' => false
            ])
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Authorization' => 'Basic ' . $this->credentials,
                ])
                ->post($this->baseUrl . "pix/in/dynamic-qrcode", [
                    'amount' => (float) $data['amount'],
                    'accountId' => $this->accountId,
                    'userId' => $this->userId,
                    'description' => $data['description'] ?? 'Description in pix transaction',
                    'payerName' => $data['name'],
                    'payerCpfCnpj' => $data['document'],
                    'expirationSeconds' => $data['expire']
                ]);




            $data = array(
                'pix' => $response->json()['data']['emv'],
                'qrcode' => $response->json()['data']['emv'],
                'uuid' => $response->json()['data']['txId'],
                'externalId' => $data['externalId'],
                'amount' => number_format($data['amount'], 2, ',', '.'),
                'createdAt' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'expire' => $response->json()['data']['expire'] ?? 3600,
            );
            return [
                'statusCode' => $response->status(),
                'data' => $data,
                'acquirer' => 'Owen'
            ];
        } catch (\Exception $e) {
            Log::error('Owen create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'Owen'
            ];
        }
    }

    public function createChargeWithdraw(array $data, string $token)
    {



        try {
            $response = Http::withOptions([
                //  'verify' => false
            ])
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Authorization' => 'Basic ' . $this->credentials,
                ])
                ->post($this->baseUrl . "bank-accounts/" . $this->accountId . "/transfer/external", [
                    'amount' => (float) $data['amount'],
                    'pixKey' => $data['pixKey'],
                    'externalId' => $data['externalId'],
                    'description' => $data['description'] ?? 'Description in pix transaction',
                ]);



            $data = array(
                'uuid' => $response->json()['data']['metadata']['idempotencyKey'],
                'externalId' => $response->json()['data']['externalId'],
                'amount' => number_format($data['amount'], 2, ',', '.'),
                'createdAt' => $response->json()['createdAt'] ?? now(),
                'status' => 'pending',
                'expire' => $response->json()['expire'] ?? 3600,
            );
            return [
                'statusCode' => $response->status(),
                'data' => $data,
                'acquirer' => 'Owen'
            ];
        } catch (\Exception $e) {
            Log::error('Owen create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'Owen'
            ];
        }
    }

    public function verifyCharge(string $payInId, string $token)
    {


        try {

            $response = Http::withHeaders([
                'Content-Type' => 'application/json', // Essencial para o corpo JSON
                'Accept' => 'application/json', // Boa prática para indicar que você espera JSON
                'Authorization' => 'Basic ' . $this->credentials,
            ])
                ->withOptions([
                    //       'verify' => false
                ])
                // Usar send() permite controlar o método e o corpo separadamente
                ->send('GET', $this->baseUrl . 'pix/in/dynamic-qrcode/' . $payInId, []);




            //dd($response->json());


            return [
                'statusCode' => $response->status(),
                'data' => $response->json()['data']
            ];
        } catch (\Exception $e) {
            Log::error('Owen create charge exception', [
                'message' => $e->getMessage(),
                'data' => $payInId
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'Owen'
            ];
        }
    }

    public function createChargeRefund(array $data, string $token)
    {


        try {
            $response = Http::withOptions([
                // 'verify' => false
            ])
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Authorization' => 'Basic ' . $this->credentials,
                ])
                ->post($this->baseUrl . "pix/in/refund/" . $data['endToEndId'], []);

            // dd($response->json());

            $data = array(
                'uuid' => $response->json()['data']['id'],
                'status' => $response->json()['data']['status'],
                'amount' => number_format($response->json()['data']['amount'], 2, ',', '.'),
                'endToEndId' => $response->json()['data']['endToEndId'],
            );

            return [
                'statusCode' => $response->status(),
                'data' => $data,
                'acquirer' => 'Owen'
            ];
        } catch (\Exception $e) {
            Log::error('Owen create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'Owen'
            ];
        }
    }

    public function verifyChargePayOut(string $end2end, string $token)
    {


        try {

            $response = Http::withHeaders([
                'Content-Type' => 'application/json', // Essencial para o corpo JSON
                'Accept' => 'application/json', // Boa prática para indicar que você espera JSON
                'Authorization' => 'Basic ' . $this->credentials,
            ])
                ->withOptions([
                    //  'verify' => false
                ])
                // Usar send() permite controlar o método e o corpo separadamente
                ->send('GET', $this->baseUrl . 'bank-accounts/' . $this->accountId . '/transfer/external/' . $end2end, []);





            return [
                'statusCode' => $response->status(),
                'data' => $response->json()['data']
            ];
        } catch (\Exception $e) {
            Log::error('Owen create charge exception', [
                'message' => $e->getMessage(),
                'data' => $end2end
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'Owen'
            ];
        }
    }




    protected function ensureTrailingSlash($url)
    {
        return rtrim($url, '/') . '/';
    }
}
