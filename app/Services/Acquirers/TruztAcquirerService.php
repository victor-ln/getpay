<?php

namespace App\Services\Acquirers;

use App\Models\Bank;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Acquirers\AcquirerInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;



class TruztAcquirerService implements AcquirerInterface
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
                ->post($this->baseUrl . 'GerarToken', [
                    'username' => $this->username,
                    'password' => $this->password
                ]);






            if ($response['jwt']) {
                return $response['jwt'];
            }



            Log::error('lumepay authentication error', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            $user = Auth::user();
            Log::error('lumepay authentication exception' . $user->id, [
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
                    'verify' => false
                ])



                ->post($this->baseUrl . "CreatePayin", [
                    'externalId' => $data['externalId'],
                    'amount' => (float) $data['amount'],
                    'username' => $this->username,
                    'password' => $this->password,
                    'webhook' => 'https://app-getpay-prod-01.azurewebsites.net/api/webhook/handler'

                ]);




            $data = array(
                'pix' => $response->json()['pix']['brcode'],
                'qrcode' => $response->json()['pix']['qrcode'],
                'uuid' => $response->json()['pix']['id'],
                'externalId' => $response->json()['pix']['externalId'],
                'amount' => number_format($data['amount'], 2, ',', '.'),
                'createdAt' => $response->json()['pix']['createdAt'],
                'status' => 'pending',
                'expire' => $response->json()['pix']['expire'] ?? 3600,
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



                ->post($this->baseUrl . "CreatePayout", [
                    'username' => $this->username,
                    'password' => $this->password,
                    'amount' => (float) $data['amount'],
                    'webhook' => 'https://app-getpay-prod-01.azurewebsites.net/api/webhook/handler',
                    'document' => $data['documentNumber'],
                    'pixKey' => $data['pixKey'],
                    'pixKeyType' => $data['pixKeyType'],
                    'externalId' => $data['externalId'],
                    'validate_document' => false
                ]);



            $data = array(
                'uuid' => $response->json()['pix']['id'],
                'externalId' => $response->json()['pix']['externalId'],
                'amount' => number_format($data['amount'], 2, ',', '.'),
                'createdAt' => $response->json()['pix']['createdAt'] ?? now(),
                'status' => 'pending',
                'expire' => $response->json()['pix']['expire'] ?? 3600,
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

    public function verifyCharge(string $payInId, string $token)
    {


        try {

            $response = Http::withToken($token)


                ->withHeaders([
                    'X-PayIn-Id' => $payInId,
                    'Content-Type' => 'application/json', // Essencial para o corpo JSON
                    'Accept' => 'application/json', // Boa prática para indicar que você espera JSON
                ])
                ->withOptions([
                    'verify' => false
                ])
                // Usar send() permite controlar o método e o corpo separadamente
                ->send('POST', $this->baseUrl . 'GetPayIn', [
                    'json' => [ // 'json' fará com que os dados sejam enviados como JSON no corpo
                        'username' => $this->username,
                        'password' => $this->password,
                    ]
                ]);







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
                    'X-PayOut-Id' => $payOut,
                    'Content-Type' => 'application/json', // Essencial para o corpo JSON
                    'Accept' => 'application/json', // Boa prática para indicar que você espera JSON
                ])
                ->withOptions([
                    'verify' => false
                ])
                // Usar send() permite controlar o método e o corpo separadamente
                ->send('POST', $this->baseUrl . 'GetPayOut', [
                    'json' => [ // 'json' fará com que os dados sejam enviados como JSON no corpo
                        'username' => $this->username,
                        'password' => $this->password,
                    ]
                ]);





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
