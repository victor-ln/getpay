<?php

namespace App\Http\Controllers;

use App\Models\{Bank, Payment, Account};
use App\Services\BankKpiService;
use Illuminate\Http\Request;
use App\Traits\ToastTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AcquirerResolverService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankController extends Controller
{

    use ToastTrait;

    protected $kpiService;
    protected $acquirerResolver;

    public function __construct(BankKpiService $kpiService, AcquirerResolverService $acquirerResolver)
    {
        $this->kpiService = $kpiService;
        $this->acquirerResolver = $acquirerResolver;
    }
    public function index()
    {
        $banks = Bank::all();

        // Separa os bancos em duas coleções para as abas
        $activeBanks = $banks->where('active', true);
        $inactiveBanks = $banks->where('active', false);

        return view('banks.index', compact('activeBanks', 'inactiveBanks'));
    }


    /**
     * Busca e retorna os dados detalhados de um banco para o modal.
     */
    public function details(Bank $bank)
    {
        // Calcula o total em custódia (a partir da sua tabela 'balances')
        $custody = DB::table('balances')
            ->where('acquirer_id', $bank->id)
            ->selectRaw('SUM(available_balance + blocked_balance) as total')
            ->first()->total ?? 0;

        // ✅ [A ADIÇÃO] Busca os clientes e o saldo individual de cada um neste banco
        $clientsWithBalance = DB::table('accounts as a')
            ->join('balances as b', function ($join) use ($bank) {
                $join->on('a.id', '=', 'b.account_id')
                    ->where('b.acquirer_id', '=', $bank->id);
            })
            ->where('a.acquirer_id', $bank->id)
            ->select(
                'a.name',
                DB::raw('b.available_balance + b.blocked_balance as total_balance')
            )
            ->get()
            ->map(function ($client) {
                // Formata os dados para o JavaScript
                return [
                    'name' => $client->name,
                    'balance_formatted' => number_format($client->total_balance, 2, ',', '.')
                ];
            });

        // Busca o saldo real da API da liquidante (sua lógica existente)
        $acquirerBalance = 'N/A';
        try {
            $acquirerService = $this->acquirerResolver->resolveByBank($bank);
            if (method_exists($acquirerService, 'getBalance')) {
                $token = $acquirerService->getToken();
                $response = $acquirerService->getBalance($token);
                if (isset($response['data']['balance'])) {
                    $acquirerBalance = $response['data']['balance'];
                }
            }
        } catch (\Exception $e) {
            report($e);
            $acquirerBalance = 'Error';
        }

        // Retorna todos os dados como JSON
        return response()->json([
            'bank_name' => $bank->name,
            'total_custody' => number_format($custody, 2, ',', '.'),
            'acquirer_balance' => is_numeric($acquirerBalance) ? number_format($acquirerBalance, 2, ',', '.') : $acquirerBalance,
            'active_clients' => $clientsWithBalance, // Envia a nova lista detalhada
        ]);
    }

    public function store(Request $request)
    {


        try {
            // Validação única e completa
            $validated = $request->validate([
                // Dados principais do banco
                'name' => 'required|string|max:255',
                'token' => 'nullable|string',
                'user' => 'nullable|string',
                'password' => 'nullable|string',
                'client_id' => 'nullable|string',
                'client_secret' => 'nullable|string',
                'baseurl' => 'required|string|max:255',
                'active' => 'nullable|boolean',

                // Fees
                'fees' => 'nullable|array',
                'fees.deposit' => 'nullable|array',
                'fees.deposit.fixed' => 'nullable|numeric|min:0',
                'fees.deposit.percentage' => 'nullable|numeric|min:0|max:100',
                'fees.deposit.minimum' => 'nullable|numeric|min:0',
                'fees.withdrawal' => 'nullable|array',
                'fees.withdrawal.fixed' => 'nullable|numeric|min:0',
                'fees.withdrawal.percentage' => 'nullable|numeric|min:0|max:100',
                'fees.withdrawal.minimum' => 'nullable|numeric|min:0',

                // Configuração avançada
                'is_advanced_config' => 'nullable',
                'pay_in_base_url' => 'nullable|string',
                'pay_in_client_id' => 'nullable|string',
                'pay_in_client_secret' => 'nullable|string',
                'pay_in_cert_crt' => 'nullable|file|max:5120',
                'pay_in_cert_key' => 'nullable|file|max:5120',
                'pay_in_cert_pfx' => 'nullable|file|max:5120',
                'pay_in_cert_pass' => 'nullable|string',
                'pay_out_base_url' => 'nullable|string',
                'pay_out_client_id' => 'nullable|string',
                'pay_out_client_secret' => 'nullable|string',
                'pay_out_cert_crt' => 'nullable|file|max:5120',
                'pay_out_cert_key' => 'nullable|file|max:5120',
                'pay_out_cert_pfx' => 'nullable|file|max:5120',
                'pay_out_cert_pass' => 'nullable|string',
            ]);

            \Log::info('✅ Validação passou!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ ERRO DE VALIDAÇÃO:', $e->errors());

            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ ERRO INESPERADO:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erro ao processar: ' . $e->getMessage())
                ->withInput();
        }

        // Separa dados do banco (apenas os campos da tabela banks)
        $bankData = collect($validated)->only([
            'name',
            'token',
            'user',
            'password',
            'client_id',
            'client_secret',
            'baseurl',
            'active'
        ])->toArray();

        // Define active como false se não foi enviado
        if (!isset($bankData['active'])) {
            $bankData['active'] = false;
        }

        // Cria o banco
        try {
            $bank = Bank::create($bankData);
            \Log::info('✅ Bank criado com sucesso', ['id' => $bank->id]);
        } catch (\Exception $e) {
            \Log::error('❌ Erro ao criar bank:', [
                'message' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erro ao criar banco: ' . $e->getMessage())
                ->withInput();
        }

        // Processa configuração avançada (certificados)
        if ($request->has('is_advanced_config')) {
            try {
                $bank->api_config = $this->processApiConfig($request, []);
                $bank->save();
                \Log::info('✅ API Config salvo com sucesso');
            } catch (\Exception $e) {
                \Log::error('❌ Erro ao processar certificados:', [
                    'message' => $e->getMessage()
                ]);

                // Rollback: deleta o banco criado se falhar no upload
                $bank->delete();

                return redirect()
                    ->back()
                    ->with('error', 'Erro ao fazer upload dos certificados: ' . $e->getMessage())
                    ->withInput();
            }
        }

        // Atualiza fees_config
        if (isset($validated['fees'])) {
            try {
                $bank->fees_config = $validated['fees'];
                $bank->save();
                \Log::info('✅ Fees config salvo com sucesso');
            } catch (\Exception $e) {
                \Log::error('❌ Erro ao salvar fees config:', [
                    'message' => $e->getMessage()
                ]);

                // Rollback: deleta o banco criado se falhar
                $bank->delete();

                return redirect()
                    ->back()
                    ->with('error', 'Erro ao salvar configuração de fees: ' . $e->getMessage())
                    ->withInput();
            }
        }

        \Log::info('=== STORE CONCLUÍDO COM SUCESSO ===', ['bank_id' => $bank->id]);

        return $this->updatedSuccess('Bank created successfully!', 'banks.index');
    }

    public function edit(Bank $bank)
    {
        $kpis = $this->kpiService->getKpis($bank);

        return view('banks.edit', compact('bank', 'kpis'));
    }

    public function create()
    {
        return view('banks.edit');
    }

    public function show(Bank $bank)
    {
        return $bank;
    }

    public function activate(Bank $bank)
    {
        // Desativa todos primeiro
        Bank::where('id', '!=', $bank->id)->update(['active' => 0]);

        // Ativa o banco escolhido
        $bank->update(['active' => 1]);

        return $this->updatedSuccess('Bank atived successfully!', 'banks.index');
    }

    public function update(Request $request, Bank $bank)
    {

        try {
            $validated = $request->validate([
                // Dados principais do banco
                'name' => 'sometimes|string|max:255',
                'token' => 'nullable|string',
                'user' => 'nullable|string',
                'password' => 'nullable|string',
                'client_id' => 'nullable|string',
                'client_secret' => 'nullable|string',
                'baseurl' => 'nullable|string|max:255',
                'active' => 'nullable|boolean',

                // Fees
                'fees' => 'nullable|array',
                'fees.deposit' => 'nullable|array',
                'fees.deposit.fixed' => 'nullable|numeric|min:0',
                'fees.deposit.percentage' => 'nullable|numeric|min:0|max:100',
                'fees.deposit.minimum' => 'nullable|numeric|min:0',
                'fees.withdrawal' => 'nullable|array',
                'fees.withdrawal.fixed' => 'nullable|numeric|min:0',
                'fees.withdrawal.percentage' => 'nullable|numeric|min:0|max:100',
                'fees.withdrawal.minimum' => 'nullable|numeric|min:0',

                'is_advanced_config' => 'nullable',
                'pay_in_base_url' => 'nullable|string',
                'pay_in_client_id' => 'nullable|string',
                'pay_in_client_secret' => 'nullable|string',
                'pay_in_cert_crt' => 'nullable|file|max:5120',
                'pay_in_cert_key' => 'nullable|file|max:5120',
                'pay_in_cert_pfx' => 'nullable|file|max:5120',
                'pay_in_cert_pass' => 'nullable|string',
                'pay_out_base_url' => 'nullable|string',
                'pay_out_client_id' => 'nullable|string',
                'pay_out_client_secret' => 'nullable|string',
                'pay_out_cert_crt' => 'nullable|file|max:5120',
                'pay_out_cert_key' => 'nullable|file|max:5120',
                'pay_out_cert_pfx' => 'nullable|file|max:5120',
                'pay_out_cert_pass' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {


            // Retorna com erros para a view
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('❌ ERRO INESPERADO:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erro ao processar: ' . $e->getMessage())
                ->withInput();
        }

        // Separa dados do banco
        $bankData = collect($validated)->except([
            'fees',
            'is_advanced_config',
            'pay_in_base_url',
            'pay_in_client_id',
            'pay_in_client_secret',
            'pay_in_cert_crt',
            'pay_in_cert_key',
            'pay_in_cert_pfx',
            'pay_in_cert_pass',
            'pay_out_base_url',
            'pay_out_client_id',
            'pay_out_client_secret',
            'pay_out_cert_crt',
            'pay_out_cert_key',
            'pay_out_cert_pfx',
            'pay_out_cert_pass'
        ])->toArray();

        // Remove a senha se não foi preenchida
        if (!$request->filled('password')) {
            unset($bankData['password']);
        }

        // Atualiza dados principais
        $bank->update($bankData);

        // Processa configuração avançada COM TRATAMENTO DE ERRO
        if ($request->has('is_advanced_config')) {
            try {
                $bank->api_config = $this->processApiConfig($request, $bank->api_config ?? []);
                $bank->save();
                \Log::info('✅ API Config salvo com sucesso');
            } catch (\Exception $e) {
                \Log::error('❌ Erro ao processar certificados:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()
                    ->back()
                    ->with('error', 'Erro ao fazer upload dos certificados: ' . $e->getMessage())
                    ->withInput();
            }
        }

        // Atualiza fees_config
        if (isset($validated['fees'])) {
            $bank->fees_config = $validated['fees'];
            $bank->save();
        }

        return $this->updatedSuccess('Bank updated successfully!', 'banks.index');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bank deleted successfully!',
        ]);
    }

    /**
     * Processa os dados do formulário avançado e o upload dos certificados.
     */
    private function processApiConfig(Request $request, array $existingConfig = []): array
    {
        $config = $existingConfig;



        // Extensões permitidas por tipo de certificado
        $allowedExtensions = [
            'crt' => ['crt', 'cer', 'pem', 'der'],
            'key' => ['key', 'pem'],
            'pfx' => ['pfx', 'p12']
        ];

        // Helper para processar certificados
        $processCertificates = function ($type) use ($request, &$config, $allowedExtensions) {
            $certTypes = ['crt', 'key', 'pfx'];

            foreach ($certTypes as $certType) {
                $fieldName = "{$type}_cert_{$certType}";

                if ($request->hasFile($fieldName)) {
                    try {
                        $file = $request->file($fieldName);

                        // Verifica se o arquivo é válido
                        if (!$file->isValid()) {
                            throw new \Exception("Arquivo {$fieldName} inválido: " . $file->getErrorMessage());
                        }

                        // Valida a extensão manualmente
                        $extension = strtolower($file->getClientOriginalExtension());

                        if (!in_array($extension, $allowedExtensions[$certType])) {
                            throw new \Exception(
                                "Arquivo {$fieldName} com extensão inválida. " .
                                    "Extensões permitidas: " . implode(', ', $allowedExtensions[$certType]) .
                                    ". Recebido: {$extension}"
                            );
                        }

                        \Log::info("Processando {$fieldName}", [
                            'original_name' => $file->getClientOriginalName(),
                            'extension' => $extension,
                            'size' => $file->getSize(),
                        ]);

                        // Remove arquivo antigo
                        $oldPath = $config[$type]['certificate'][$certType] ?? null;
                        if ($oldPath && Storage::disk('local')->exists($oldPath)) {
                            Storage::disk('local')->delete($oldPath);
                            \Log::info("Arquivo antigo removido: {$oldPath}");
                        }

                        // CORRIGIDO: Gera nome único preservando a extensão ORIGINAL
                        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $originalName);
                        $filename = $type . '_' . $certType . '_' . time() . '.' . $extension;

                        // Salva o arquivo
                        $path = $file->storeAs('certificates/e2', $filename, 'local');
                        $config[$type]['certificate'][$certType] = $path;

                        \Log::info("✅ {$fieldName} salvo em: {$path}", [
                            'filename_salvo' => $filename,
                            'extensao_preservada' => $extension
                        ]);
                    } catch (\Exception $e) {
                        \Log::error("Erro ao processar {$fieldName}:", [
                            'message' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }
            }

            // Senha do certificado
            $passField = "{$type}_cert_pass";
            if ($request->filled($passField)) {
                $config[$type]['certificate']['password'] = encrypt($request->input($passField));
                \Log::info("Senha do certificado {$type} atualizada");
            }
        };

        try {
            // Pay In
            if ($request->filled('pay_in_base_url')) {
                $config['pay_in']['base_url'] = $request->input('pay_in_base_url');
            }
            if ($request->filled('pay_in_client_id')) {
                $config['pay_in']['credentials']['client_id'] = $request->input('pay_in_client_id');
            }
            if ($request->filled('pay_in_client_secret')) {
                $config['pay_in']['credentials']['client_secret'] = $request->input('pay_in_client_secret');
            }
            $config['pay_in']['pix_key'] = $request->input('pay_in_pix_key');
            $processCertificates('pay_in');

            // Pay Out
            if ($request->filled('pay_out_base_url')) {
                $config['pay_out']['base_url'] = $request->input('pay_out_base_url');
            }
            if ($request->filled('pay_out_client_id')) {
                $config['pay_out']['credentials']['client_id'] = $request->input('pay_out_client_id');
            }
            if ($request->filled('pay_out_client_secret')) {
                $config['pay_out']['credentials']['client_secret'] = $request->input('pay_out_client_secret');
            }
            $processCertificates('pay_out');
        } catch (\Exception $e) {
            \Log::error('Erro em processApiConfig:', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }

        return $config;
    }

    /**
     * Calcula os KPIs para um banco específico.
     * @param \App\Models\Bank $bank
     * @return array
     */
    private function getBankKpis(Bank $bank): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfYear = $now->copy()->startOfYear();
        $endOfYear = $now->copy()->endOfYear();

        // Consulta base para transações relacionadas a este banco e que foram 'paid'
        $baseQuery = Payment::where('provider_id', $bank->id)
            ->where('status', 'paid');

        // Aplicar filtro de usuário se não for admin
        if (Auth::user()->level != 'admin') {
            $baseQuery->where('user_id', Auth::user()->id);
        }

        // --- KPIs de Entrada e Saída ---
        $inThisMonth = (clone $baseQuery)
            ->where('type_transaction', 'IN')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $inThisWeek = (clone $baseQuery)
            ->where('type_transaction', 'IN')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->sum('amount');

        $inThisYear = (clone $baseQuery)
            ->where('type_transaction', 'IN')
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->sum('amount');

        $outThisMonth = (clone $baseQuery)
            ->where('type_transaction', 'OUT')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Saldo Atual (Total IN - Total OUT para TODAS as transações do banco desde o início)
        $totalIn = (clone $baseQuery)->where('type_transaction', 'IN')->sum('amount');
        $totalOut = (clone $baseQuery)->where('type_transaction', 'OUT')->sum('amount');
        $currentBalance = $totalIn - $totalOut;

        // --- Cálculo do Total de Fees Pagos AO BANCO ---
        $totalFeesPaidToBank = 0;
        $bankFeesConfig = $bank->fees_config ?? []; // Obtém a configuração de fees do banco (pode ser vazio)

        // Fees de Depósito (Deposit Fee Percentage)
        if (isset($bankFeesConfig['deposit_percentage']) && $bankFeesConfig['deposit_percentage'] > 0) {
            $depositPercentage = $bankFeesConfig['deposit_percentage'] / 100;
            $deposits = (clone $baseQuery)
                ->where('type_transaction', 'IN')
                ->sum('amount');
            $totalFeesPaidToBank += ($deposits * $depositPercentage);
        }

        // Fees de Saque (Withdrawal Fixed)
        if (isset($bankFeesConfig['withdrawal_fixed']) && $bankFeesConfig['withdrawal_fixed'] > 0) {
            $withdrawalsCount = (clone $baseQuery)
                ->where('type_transaction', 'OUT')
                ->count(); // Conta o número de saques
            $totalFeesPaidToBank += ($withdrawalsCount * $bankFeesConfig['withdrawal_fixed']);
        }

        // Fees de Manutenção Mensal (Monthly Maintenance)
        // Isso é um pouco mais complexo, pois pode depender de qual mês estamos olhando.
        // Por simplicidade, vamos considerar que se aplica se o banco está ativo no mês.
        // Você pode ajustar para somar apenas os meses ativos desde a criação do banco.
        if (isset($bankFeesConfig['monthly_maintenance']) && $bankFeesConfig['monthly_maintenance'] > 0) {
            // Conta quantos meses completos o banco esteve ativo desde que foi criado até agora
            // ou se o KPI for só anual, conta os meses do ano atual.
            // Para o KPI 'total_fees_paid' (geral), faz mais sentido ser acumulado.
            $monthsActive = $bank->created_at->diffInMonths($now);
            $totalFeesPaidToBank += ($monthsActive * $bankFeesConfig['monthly_maintenance']);
        }

        // Fees de Transação Fixa (Flat Transaction Fee)
        if (isset($bankFeesConfig['transaction_flat']) && $bankFeesConfig['transaction_flat'] > 0) {
            $transactionsCount = (clone $baseQuery)->count(); // Conta o número total de transações (IN e OUT)
            $totalFeesPaidToBank += ($transactionsCount * $bankFeesConfig['transaction_flat']);
        }


        return [
            'in_this_month' => $inThisMonth,
            'in_this_week' => $inThisWeek,
            'in_this_year' => $inThisYear,
            'out_this_month' => $outThisMonth,
            'current_balance' => $currentBalance,
            'total_fees_paid' => $totalFeesPaidToBank, // AGORA ESTE É O FEE PAGO AO BANCO
        ];
    }
}
