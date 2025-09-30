<?php

namespace App\Services\Acquirers;

use App\Models\Bank;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Acquirers\AcquirerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;



class XdpagAcquirerService implements AcquirerInterface
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct(Bank $bank)
    {
        $this->baseUrl = $this->ensureTrailingSlash($bank->baseurl);
        $this->username = $bank->user;
        $this->password = $bank->password;
    }

    public function getToken()
    {

        try {


            $response = Http::withHeaders([
                'accept' => '*/*',
                'Content-Type' => 'application/json',
            ])
                ->withOptions([
                    'verify' => false
                ])



                // ->asForm()->post($this->baseUrl, [
                //     'grant_type' => 'client_credentials',
                //     'scope' => 'cob.read cob.write pix.read pix.write',
                // ]);
                ->post($this->baseUrl . 'account/login', [
                    'username' => $this->username,
                    'password' => $this->password
                ]);






            if ($response['access_token']) {
                return $response['access_token'];
            }



            Log::error('Xdpag authentication error', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {

            Log::error('Xdpag authentication exception', [
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function createCharge(array $data, string $token)
    {




        try {
            $response = Http::withToken($token)
                ->withOptions([
                    //    'verify' => false,
                    'allow_redirects' => false // Impede redirecionamentos automáticos
                ])
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                    'X-Requested-With' => 'XMLHttpRequest' // Indica que é uma requisição AJAX
                ])
                ->post($this->baseUrl . "order/pay-in", [
                    'amount' => (float) $data['amount'],
                    'webhook' => 'https://app-getpay-prod-3.azurewebsites.net/api/webhook/xdpag',
                    'externalId' => $data['externalId'],
                    'description' => $data['description'] ?? 'Description in pix transaction',
                    'additional_data' => [
                        [
                            'name' => 'Atenção',
                            'value' => 'Ao confirmar o PIX, você aceita que o uso do valor é sua responsabilidade.'
                        ]
                    ]

                ]);




            $data = array(
                'pix' => $response->json()['data']['brcode'],
                'qrcode' => $response->json()['data']['qrcode'],
                'uuid' => $response->json()['data']['id'],
                'externalId' => $response->json()['data']['externalId'],
                'amount' => number_format($data['amount'], 2, ',', '.'),
                'createdAt' => $response->json()['data']['createdAt'],
                'status' => 'pending',
                'expire' => $response->json()['data']['expire'] ?? 3600,
            );
            return [
                'statusCode' => $response->status(),
                'data' => $data,
                'acquirer' => 'truzt'
            ];
        } catch (\Exception $e) {
            Log::error('Truzt create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'truzt'
            ];
        }
    }

    public function createChargeWithdraw(array $data, string $token)
    {

        try {
            $response = Http::withToken($token)
                ->withOptions([
                    'verify' => false
                ])



                ->post($this->baseUrl . "order/pay-out", [
                    'amount' => (float) $data['amount'],
                    'webhook' => 'https://app-getpay-prod-3.azurewebsites.net/api/webhook/xdpag',
                    'document' => $data['documentNumber'],
                    'pixKey' => $data['pixKey'],
                    'pixKeyType' => $data['pixKeyType'],
                    'externalId' => $data['externalId'],
                ]);

            Log::info('Xdpag create charge response', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            $data = array(
                'uuid' => $response->json()['data']['id'],
                'externalId' => $response->json()['data']['externalId'],
                'amount' => number_format($data['amount'], 2, ',', '.'),
                'createdAt' => $response->json()['data']['createdAt'] ?? now(),
                'status' => 'pending',
                'expire' => $response->json()['data']['expire'] ?? 3600,
            );
            return [
                'statusCode' => $response->status(),
                'data' => $data,
                'acquirer' => 'xdpag'
            ];
        } catch (\Exception $e) {
            Log::error('Xdpag create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'Xdpag'
            ];
        }
    }

    public function verifyCharge(string $payInId, string $token)
    {


        try {

            $response = Http::withToken($token)


                ->withHeaders([
                    'Content-Type' => 'application/json', // Essencial para o corpo JSON
                    'Accept' => 'application/json', // Boa prática para indicar que você espera JSON
                ])
                ->withOptions([
                    //  'verify' => false
                ])
                // Usar send() permite controlar o método e o corpo separadamente
                ->send('GET', $this->baseUrl . 'order/pay-in/' . $payInId, []);







            return [
                'statusCode' => $response->status(),
                'data' => $response->json()['data']
            ];
        } catch (\Exception $e) {
            Log::error('Truzt create charge exception', [
                'message' => $e->getMessage(),
                'data' => $payInId
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'truzt'
            ];
        }
    }

    public function createChargeRefund(array $data, string $token)
    {

        try {
            $response = Http::withToken($token)


                ->patch($this->baseUrl . "RefundPixIn", [
                    'username' => $this->username,
                    'password' => $this->password,
                    'Id' => $data['provider_transaction_id'],
                ]);



            $data = array(
                'uuid' => $response->json()['data']['id'],
                'status' => $response->json()['data']['status'],
                'amount' => number_format($response->json()['data']['amount'], 2, ',', '.'),
                'endToEndId' => $response->json()['data']['endToEndId'],
            );

            return [
                'statusCode' => $response->status(),
                'data' => $data,
                'acquirer' => 'truzt'
            ];
        } catch (\Exception $e) {
            Log::error('Truzt create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'truzt'
            ];
        }
    }

    public function verifyChargePayOut(string $payOut, string $token)
    {


        try {

            $response = Http::withToken($token)


                ->withHeaders([
                    'Content-Type' => 'application/json', // Essencial para o corpo JSON
                    'Accept' => 'application/json', // Boa prática para indicar que você espera JSON
                ])
                ->withOptions([
                    //  'verify' => false
                ])
                // Usar send() permite controlar o método e o corpo separadamente
                ->send('GET', $this->baseUrl . 'order/pay-out/' . $payOut, []);





            return [
                'statusCode' => $response->status(),
                'data' => $response->json()['data']
            ];
        } catch (\Exception $e) {
            Log::error('Truzt create charge exception', [
                'message' => $e->getMessage(),
                'data' => $payOut
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'truzt'
            ];
        }
    }

    protected function ensureTrailingSlash($url)
    {
        return rtrim($url, '/') . '/';
    }
}
