<?php

namespace App\Services\Acquirers;

use App\Models\Bank;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class E2AcquirerService
{
    protected $bank;
    protected $payInConfig;
    protected $payOutConfig;

    /**
     * O construtor recebe o banco e imediatamente lê e organiza a configuração da API.
     */
    public function __construct(Bank $bank)
    {
        $this->bank = $bank;

        // O Laravel já converte o JSON da coluna 'api_config' para um array
        $config = $bank->api_config;

        if (empty($config['pay_in']) || empty($config['pay_out'])) {
            throw new Exception("Configuração da API incompleta para o banco E2 (ID: {$bank->id}). Verifique a coluna 'api_config'.");
        }

        $this->payInConfig = $config['pay_in'];
        $this->payOutConfig = $config['pay_out'];
    }

    /**
     * Método público esperado pelo PaymentService
     */
    public function getToken(): ?string
    {
        return "85698542637hjhgkas";
    }

    /**
     * Obtém token de autenticação para Pay In
     */
    public function getPayInToken(): ?string
    {
        $type = "pay_in";
        $config = $type === 'pay_in' ? $this->payInConfig : $this->payOutConfig;
        $baseUrl = rtrim($config['base_url'] ?? '', '/') . '/';
        $credentials = $config['credentials'] ?? [];
        $certificates = $config['certificate'] ?? [];



        try {
            // Validação de credenciais
            if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
                throw new Exception("Credenciais não configuradas para {$type}");
            }

            Log::info("E2Service: Solicitando token de {$type}.", [
                'base_url' => $baseUrl,
                'client_id' => $credentials['client_id']
            ]);

            // Prepara o corpo da requisição
            $body = [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
                'grant_type' => 'client_credentials'
            ];

            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);

            // Faz a requisição de autenticação
            $response = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'API Client/1.0'
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                ->post($baseUrl . 'oauth/token', $body);
            // ->post($baseUrl . 'api/v2/oauth/token', $body);

            // Verifica se a requisição foi bem-sucedida
            if ($response->successful()) {
                $token = $response->json('access_token');

                if ($token) {
                    Log::info("E2Service: Token de {$type} obtido com sucesso.");
                    return $token;
                }

                Log::error("E2Service: Resposta não contém 'access_token'.", [
                    'type' => $type,
                    'response' => $response->json()
                ]);
                return null;
            }

            // Log de erro com detalhes da resposta
            Log::error("E2Service: Falha ao obter token de {$type}.", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao obter token de {$type}.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao obter token de {$type}.", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Obtém token de autenticação para Pay Out
     */
    public function getPayOutToken(): ?string
    {
        $type = "pay_out";
        $config = $type === 'pay_in' ? $this->payInConfig : $this->payOutConfig;
        $baseUrl = rtrim($config['base_url'] ?? '', '/') . '/';
        $credentials = $config['credentials'] ?? [];
        $certificates = $config['certificate'] ?? [];



        try {
            // Validação de credenciais
            if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
                throw new Exception("Credenciais não configuradas para {$type}");
            }

            Log::info("E2Service: Solicitando token de {$type}.", [
                'base_url' => $baseUrl,
                'client_id' => $credentials['client_id']
            ]);

            // Prepara o corpo da requisição
            $body = [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
                'grant_type' => 'client_credentials'
            ];

            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);

            // Faz a requisição de autenticação
            $response = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'API Client/1.0'
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                // ->post($baseUrl . 'oauth/token', $body);
                ->post($baseUrl . 'api/v2/oauth/token', $body);

            // Verifica se a requisição foi bem-sucedida
            if ($response->successful()) {
                $token = $response->json('access_token');

                if ($token) {
                    Log::info("E2Service: Token de {$type} obtido com sucesso.");
                    return $token;
                }

                Log::error("E2Service: Resposta não contém 'access_token'.", [
                    'type' => $type,
                    'response' => $response->json()
                ]);
                return null;
            }

            // Log de erro com detalhes da resposta
            Log::error("E2Service: Falha ao obter token de {$type}.", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao obter token de {$type}.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao obter token de {$type}.", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Método central de autenticação que suporta certificados SSL/TLS
     * 
     * @param string $type 'pay_in' ou 'pay_out'
     * @return string|null Token de acesso ou null em caso de falha
     */
    protected function authenticate(string $type): ?string
    {
        // $type = "pay_out";
        $config = $type === 'pay_in' ? $this->payInConfig : $this->payOutConfig;
        $baseUrl = rtrim($config['base_url'] ?? '', '/') . '/';
        $credentials = $config['credentials'] ?? [];
        $certificates = $config['certificate'] ?? [];



        try {
            // Validação de credenciais
            if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
                throw new Exception("Credenciais não configuradas para {$type}");
            }

            Log::info("E2Service: Solicitando token de {$type}.", [
                'base_url' => $baseUrl,
                'client_id' => $credentials['client_id']
            ]);

            // Prepara o corpo da requisição
            $body = [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
                'grant_type' => 'client_credentials'
            ];

            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);

            // Faz a requisição de autenticação
            $response = Http::asForm()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'API Client/1.0'
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                // ->post($baseUrl . 'oauth/token', $body);
                ->post($baseUrl . 'api/v2/oauth/token', $body);

            // Verifica se a requisição foi bem-sucedida
            if ($response->successful()) {
                $token = $response->json('access_token');

                if ($token) {
                    Log::info("E2Service: Token de {$type} obtido com sucesso.");
                    return $token;
                }

                Log::error("E2Service: Resposta não contém 'access_token'.", [
                    'type' => $type,
                    'response' => $response->json()
                ]);
                return null;
            }

            // Log de erro com detalhes da resposta
            Log::error("E2Service: Falha ao obter token de {$type}.", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao obter token de {$type}.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao obter token de {$type}.", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Constrói as opções HTTP incluindo certificados SSL/TLS
     * 
     * @param array $certificates Configuração de certificados
     * @return array Opções para withOptions()
     */
    protected function buildHttpOptions(array $certificates): array
    {
        $options = ['verify' => false];

        // Verifica se deve usar certificado .pfx (PKCS#12)
        if (isset($certificates['pfx']) && Storage::disk('local')->exists($certificates['pfx'])) {
            Log::info("E2Service: Convertendo certificado .pfx para .pem", [
                'path' => $certificates['pfx']
            ]);

            try {
                $pfxPath = storage_path('app/' . $certificates['pfx']);
                $password = isset($certificates['password']) ? decrypt($certificates['password']) : '';

                // Converte .pfx para .pem em tempo real
                $pemPath = $this->convertPfxToPem($pfxPath, $password);

                $options['cert'] = $pemPath;
                $options['ssl_key'] = $pemPath;

                Log::info("E2Service: Certificado .pfx convertido com sucesso");
            } catch (\Exception $e) {
                Log::error("E2Service: Erro ao converter .pfx para .pem", [
                    'message' => $e->getMessage()
                ]);
                throw $e;
            }
        } elseif (
            isset($certificates['crt']) &&
            isset($certificates['key']) &&
            Storage::disk('local')->exists($certificates['crt']) &&
            Storage::disk('local')->exists($certificates['key'])
        ) {
            // Usa par .crt + .key
            Log::info("E2Service: Usando certificado .crt/.key", [
                'crt' => $certificates['crt'],
                'key' => $certificates['key']
            ]);

            $options['cert'] = storage_path('app/' . $certificates['crt']);

            // Verifica se a chave privada tem senha
            if (isset($certificates['password'])) {
                $options['ssl_key'] = [
                    storage_path('app/' . $certificates['key']),
                    decrypt($certificates['password'])
                ];
            } else {
                $options['ssl_key'] = storage_path('app/' . $certificates['key']);
            }
        } else {
            Log::warning("E2Service: Nenhum certificado SSL configurado, tentando sem mTLS");
        }

        return $options;
    }

    /**
     * Cria um pagamento (Pay In)
     * 
     * @param array $data Dados do pagamento
     * @return array Resposta da API
     * @throws Exception
     */
    public function createCharge(array $data, string $token = null): array
    {
        $token = $this->getPayInToken();



        if (!$token) {
            throw new Exception("Não foi possível autenticar para criar o pagamento.");
        }

        $baseUrl = rtrim($this->payInConfig['base_url'], '/') . '/';
        $certificates = $this->payInConfig['certificate'] ?? [];

        Log::info("E2Service: Iniciando criação de Pay In.", [
            'base_url' => $baseUrl,
            'data' => $data
        ]);

        try {
            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);

            $body = [
                // O calendário define a expiração do PIX em segundos
                'calendario' => [
                    'expiracao' => 3600 // Exemplo: 1 hora de expiração
                ],

                // Dados do devedor (quem vai pagar o PIX)
                'devedor' => [
                    // Garante que apenas os números do documento são enviados
                    'cpf' => preg_replace('/[^0-9]/', '', $data['document']),
                    'nome' => $data['name']
                ],

                // O valor da transação
                'valor' => [
                    // A API espera o valor como uma string
                    'original' => (string) number_format($data['amount'], 2, '.', ''),
                    'modalidadeAlteracao' => 0 // 0 = não permite alterar o valor
                ],

                // A chave PIX da sua conta que irá receber o dinheiro
                // O ideal é que isto venha da sua configuração em `api_config`
                'chave' => $this->payInConfig['pix_key'],

                // Descrição que aparece para o pagador
                'solicitacaoPagador' => $data['description'] ?? $data['externalId'],

                // Campos opcionais para a sua referência interna
                'infoAdicionais' => [
                    [
                        'nome' => 'Atenção!',
                        'valor' => 'Ao realizar o pagamento você concorda com os termos.'
                    ],
                    [
                        'nome' => 'ID Externo',
                        'valor' => $data['externalId'] ?? ''
                    ]
                ]
            ];

            // Faz a requisição para criar o pagamento
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0'
                ])
                ->timeout(10)
                ->withOptions($httpOptions)
                ->post($baseUrl . 'cob', $body);



            // Verifica se foi bem-sucedido
            if ($response->successful()) {

                $data = array(
                    'pix' => $response->json()['pixCopiaECola'],
                    'uuid' => $response->json()['txid'],
                    'externalId' => $data['externalId'],
                    'amount' => number_format($response->json()['valor']['original'], 2, ',', '.'),
                    'createdAt' => $response->json()['calendario']['criacao'],
                    'expire' => $response->json()['calendario']['expiracao'],
                    'status' => 'pending',
                    'qrcode' => $response->json()['pixCopiaECola'],
                );
                Log::info("E2Service: Pagamento criado com sucesso.", [
                    'response' => $response->json()
                ]);
                return [
                    'statusCode' => $response->status(),
                    'data' => $data,
                    'acquirer' => 'dubai'
                ];
            }

            Log::error("E2Service: Falha ao criar pagamento.", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            throw new Exception("Falha ao criar pagamento: " . $response->body());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao criar pagamento.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            throw new Exception("Erro na requisição: " . $e->response->body());
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao criar pagamento.", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cria um saque/transferência (Pay Out)
     * 
     * @param array $data Dados do saque
     * @return array Resposta da API
     * @throws Exception
     */
    public function createChargeWithdraw(array $data, string $token = null): array
    {

        $token = $this->getPayOutToken();



        if (!$token) {
            throw new Exception("Não foi possível autenticar para criar o saque.");
        }

        $baseUrl = rtrim($this->payOutConfig['base_url'], '/') . '/';
        $certificates = $this->payOutConfig['certificate'] ?? [];

        Log::info("E2Service: Iniciando criação de Pay Out.", [
            'base_url' => $baseUrl,
            'data' => $data
        ]);

        try {
            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);

            $cleanDocument = preg_replace('/[^0-9]/', '', $data['documentNumber']);

            if ($data['pixKeyType'] === 'PHONE') {
                $data['pixKey']  = $this->formatPhoneForPix($data['pixKey']);
            }


            $body = [
                // Chave PIX do destinatário
                'pixKey' => $data['pixKey'],

                // Documento do destinatário
                'creditorDocument' => $cleanDocument,

                // ID de ponta a ponta (se você já o tiver, senão pode ser gerado pela API)
                'endToEndId' => $data['endToEndId'] ?? null,

                // Prioridade do pagamento
                'priority' => 'HIGH', // Pode ser um valor fixo ou vir de $data

                // Descrição da transação
                'description' => $data['description'] ?? 'Pagamento de serviço',

                // Fluxo do pagamento
                'paymentFlow' => 'INSTANT', // Geralmente é um valor fixo para PIX

                // Expiração em segundos (ex: 10 minutos)
                'expiration' => 600,

                // Detalhes do pagamento (valor e moeda)
                'payment' => [
                    'currency' => 'BRL',
                    'amount' => (float) $data['amount'] // Garante que o valor é um float
                ],

                // Lista de bancos (ISPB) a serem bloqueados, se houver
                'ispbDeny' => $data['ispbDeny'] ?? [] // Um array vazio se não houver nenhum
            ];
            // Faz a requisição para criar o saque
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                    'x-idempotency-key' => $data['externalId']
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                ->post($baseUrl . 'api/v2/pix/payments/dict', $body);



            // Verifica se foi bem-sucedido
            if ($response->successful()) {
                $data = array(
                    'uuid' => $response->json()['data']['id'],
                    'externalId' => $response->json()['data']['idempotencyKey'],
                    'amount' => number_format($response->json()['data']['payment']['amount'], 2, ',', '.'),
                    'createdAt' => $response->json()['data']['createdAt'] ?? now(),
                    'status' => 'pending',
                    'expire' => $response->json()['expire'] ?? 3600,
                );
                return [
                    'statusCode' => $response->status(),
                    'data' => $data,
                    'acquirer' => 'Owen'
                ];
            }

            Log::error("E2Service: Falha ao criar saque.", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            throw new Exception("Falha ao criar saque: " . $response->body());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao criar saque.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            throw new Exception("Erro na requisição: " . $e->response->body());
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao criar saque.", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Consulta o status de uma transação
     * 
     * @param string $transactionId ID da transação
     * @param string $type 'pay_in' ou 'pay_out'
     * @return array Status da transação
     * @throws Exception
     */
    public function getTransactionStatus(string $transactionId, string $type = 'pay_in'): array
    {
        $token = $type === 'pay_in' ? $this->getPayInToken() : $this->getPayOutToken();

        if (!$token) {
            throw new Exception("Não foi possível autenticar para consultar a transação.");
        }

        $config = $type === 'pay_in' ? $this->payInConfig : $this->payOutConfig;
        $baseUrl = rtrim($config['base_url'], '/') . '/';
        $certificates = $config['certificate'] ?? [];

        Log::info("E2Service: Consultando status de transação.", [
            'type' => $type,
            'transaction_id' => $transactionId
        ]);

        try {
            $httpOptions = $this->buildHttpOptions($certificates);

            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'API Client/1.0'
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                ->get($baseUrl . "api/transactions/{$transactionId}");

            if ($response->successful()) {
                Log::info("E2Service: Status obtido com sucesso.", [
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            throw new Exception("Falha ao consultar transação: " . $response->body());
        } catch (\Exception $e) {
            Log::error("E2Service: Erro ao consultar transação.", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function convertPfxToPem(string $pfxPath, string $password = ''): string
    {
        $pfxContent = file_get_contents($pfxPath);
        $certs = [];

        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            throw new \Exception("Falha ao converter .pfx para .pem. Verifique a senha.");
        }

        $pemContent = $certs['pkey'] . "\n" . $certs['cert'];

        if (isset($certs['extracerts']) && is_array($certs['extracerts'])) {
            foreach ($certs['extracerts'] as $cert) {
                $pemContent .= "\n" . $cert;
            }
        }

        $pemPath = str_replace('.pfx', '.pem', $pfxPath);
        file_put_contents($pemPath, $pemContent);
        chmod($pemPath, 0600);

        return $pemPath;
    }

    public function getBalance(string $token = null): array
    {

        $token = $this->getPayOutToken();



        if (!$token) {
            throw new Exception("Não foi possível autenticar para criar o saque.");
        }

        $baseUrl = rtrim($this->payOutConfig['base_url'], '/') . '/';
        $certificates = $this->payOutConfig['certificate'] ?? [];

        Log::info("E2Service: Iniciando busca de saldo.", [
            'base_url' => $baseUrl,
        ]);

        try {
            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);


            // Faz a requisição para criar o saque
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                ->send('GET', $baseUrl . 'api/v2/accounts/balances/', []);

            Log::info('Resposta da API getBalance:', [
                'status_code' => $response->status(),
                'response_body' => $response->json()['data'], // Usamos body() para ver o texto bruto
            ]);



            $data = array(
                'balance' => $response->json()['data'][0]['balanceAmount']['available']
            );

            return [
                'statusCode' => $response->status(),
                'data' => $data,
            ];

            throw new Exception("Falha ao criar saque: " . $response->body());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao criar saque.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            throw new Exception("Erro na requisição: " . $e->response->body());
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao criar saque.", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function verifyChargeOut(string $token = null, string $end2end): array
    {



        $token = $this->getPayOutToken();






        if (!$token) {
            throw new Exception("Não foi possível autenticar para criar o saque.");
        }

        $baseUrl = rtrim($this->payOutConfig['base_url'], '/') . '/';
        $certificates = $this->payOutConfig['certificate'] ?? [];

        Log::info("E2Service: Iniciando busca de saldo.", [
            'base_url' => $baseUrl,
        ]);



        try {
            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);


            // Faz a requisição para criar o saque
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                ->send('GET', $baseUrl . 'api/v2/pix/payments/' . $end2end, []);







            return [
                'statusCode' => $response->status(),
                'data' => $response->json(),
            ];

            throw new Exception("Falha ao criar saque: " . $response->body());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao criar saque.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            throw new Exception("Erro na requisição: " . $e->response->body());
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao criar saque.", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function verifyChargeIn(string $token = null, string $txId): array
    {



        $token = $this->getPayInToken();






        if (!$token) {
            throw new Exception("Não foi possível autenticar para criar o saque.");
        }

        $baseUrl = rtrim($this->payInConfig['base_url'], '/') . '/';
        $certificates = $this->payInConfig['certificate'] ?? [];

        Log::info("E2Service: Iniciando busca de saldo.", [
            'base_url' => $baseUrl,
        ]);



        try {
            // Prepara opções HTTP com certificados
            $httpOptions = $this->buildHttpOptions($certificates);


            // Faz a requisição para criar o saque
            $response = Http::withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'API Client/1.0',
                ])
                ->timeout(30)
                ->withOptions($httpOptions)
                ->send('GET', $baseUrl . 'cob/' . $txId, []);







            return [
                'statusCode' => $response->status(),
                'data' => $response->json(),
            ];

            throw new Exception("Falha ao criar saque: " . $response->body());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("E2Service: Erro HTTP ao criar saque.", [
                'status' => $e->response->status(),
                'response' => $e->response->body()
            ]);
            throw new Exception("Erro na requisição: " . $e->response->body());
        } catch (\Exception $e) {
            Log::error("E2Service: Exceção ao criar saque.", [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    private function formatPhoneForPix(string $phone): string
    {
        // Remove todos os caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove o código do país se já existir no início
        if (str_starts_with($phone, '55')) {
            $phone = substr($phone, 2);
        }

        // Valida se tem pelo menos DDD + número (mínimo 10 dígitos)
        if (strlen($phone) < 10) {
            throw new \InvalidArgumentException('Número de telefone inválido. Deve conter DDD + número.');
        }

        // Valida se tem no máximo 11 dígitos (DDD + 9 dígitos)
        if (strlen($phone) > 11) {
            throw new \InvalidArgumentException('Número de telefone inválido. Formato esperado: DDD + número (10 ou 11 dígitos).');
        }

        // Retorna no formato +55DDDNÚMERO
        return '+55' . $phone;
    }
}
