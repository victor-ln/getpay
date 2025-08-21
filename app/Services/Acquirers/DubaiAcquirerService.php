<?php

namespace App\Services\Acquirers;

use App\Models\Bank;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Acquirers\AcquirerInterface;



class DubaiAcquirerService implements AcquirerInterface
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
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
            ])
                ->withOptions([
                    'verify' => false,
                ])
                ->post($this->baseUrl . 'auth/sign-in', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);



            if ($response['statusCode'] === 200) {
                return $response['access_token'];
            }



            Log::error('Dubai authentication error', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Dubai authentication exception', [
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
                    'verify' => false,
                ])
                ->post($this->baseUrl . "pix/create-immediate-qrcode", [
                    'externalId' => $data['externalId'],
                    'amount' => (float) $data['amount'],
                    'document' => $data['document'],
                    'name' => $data['name'],
                    'identification' => $data['identification'] ?? 'GETPAY',
                    'expire' => $data['expire'] ?? 3600,
                    'description' => $data['description'] ?? 'GETPAY IN',
                    'generatedBy' => 'DUBAI_CASH',
                    'type' => 'DYNAMIC'
                ]);



            $data = array(
                'pix' => $response->json()['data']['key'],
                'uuid' => $response->json()['data']['uuid'],
                'externalId' => $response->json()['data']['externalId'],
                'amount' => number_format($response->json()['data']['amount'], 2, ',', '.'),
                'createdAt' => $response->json()['data']['createdAt'],
                'expire' => $response->json()['data']['expire'],
            );

            return [
                'statusCode' => $response->status(),
                'data' => $data,
                'acquirer' => 'dubai'
            ];
        } catch (\Exception $e) {
            Log::error('Dubai create charge exception', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'dubai'
            ];
        }
    }



    public function createChargeWithdraw(array $data, string $token)
    {

        $dados = $data;

        $randomNumber = mt_rand(100000000000, 999999999999); // 12 dígitos
        $getpay = 'GETPAY ' . $randomNumber;

        $traceId = \Illuminate\Support\Str::uuid()->toString();
        Log::info("[TRACE:{$traceId}] --- INÍCIO DO PROCESSAMENTO no gateway da adquirente ---");
        $T1 = microtime(true); // Tempo inicial

        try {
            $response = Http::withToken($token)
                ->withOptions([
                    'verify' => false
                ])
                ->timeout(3)
                ->post($this->baseUrl . "pix/withdraw", [
                    'externalId' => $data['externalId'],
                    'name' => $data['name'],
                    'documentNumber' => $data['documentNumber'],
                    'key' => $data['pixKey'],
                    'description' => $data['description'] ?? '',
                    'bank' => $data['bank'] ?? '',
                    'branch' => $data['branch'] ?? '',
                    'account' => '',
                    'amount' => (float) $data['amount'],
                    'memo' => $getpay
                ]);

            Log::info("[TRACE:{$traceId}] Aguardando resposta da aquirente...");
            $T2 = microtime(true);

            Log::info("[TRACE:{$traceId}] Resposta recebida da adquirente DENTRO do timeout.", [
                'status' => $response->status(),
                'body' => $response->json()
            ]);


            if ($response->successful()) {
                $data = array(
                    'uuid' => $response->json()['data']['uuid'],
                    'externalId' => $response->json()['data']['externalId'],
                    'amount' => number_format($data['amount'], 2, ',', '.'),
                    'createdAt' => $response->json()['data']['createdAt'] ?? now(),
                    'status' => 'pending',
                    'expire' => $response->json()['data']['expire'] ?? 3600,
                );


                return [
                    'statusCode' => $response->status(),
                    'data' => $data,
                    'acquirer' => 'dubai'
                ];
            } else {
                // Se a API respondeu com erro, reverta os fundos
                throw new \Exception("A adquirente respondeu com um erro: " . $response->status());
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {

            // 3. O "SUCESSO" ESPERADO: Aconteceu o timeout.
            Log::warning("[TRACE:{$traceId}] Timeout de 3s atingido. Assumindo que a requisição foi recebida pela adquirente e está em processamento.", [
                'externalId' => $data['externalId']
            ]);


            $data = array(
                'uuid' => null,
                'externalId' => $dados['externalId'],
                'amount' => number_format($dados['amount'], 2, ',', '.'),
                'createdAt' => Now()->toIso8601String(),
                'status' => 'pending',
                'expire' => 3600,
            );


            return [
                'statusCode' => 202,
                'data' => $data,
                'acquirer' => 'dubai'
            ];
        } catch (\Exception $e) {
            // 4. ERRO REAL: Aconteceu outro problema (ex: erro de lógica)
            Log::error("[TRACE:{$traceId}] Erro inesperado ao tentar enviar o saque.", [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'statusCode' => 500,
                'status' => 'failed',
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'dubai'
            ];
        }
    }

    protected function ensureTrailingSlash($url)
    {
        return rtrim($url, '/') . '/';
    }
}
