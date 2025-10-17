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
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'token' => 'nullable|string',
            'user' => 'nullable|string',
            'password' => 'nullable|string',
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
            'config' => 'nullable',
            'baseurl' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        $feesData = $request->validate([
            'fees' => 'nullable|array',
            'fees.deposit.fixed' => 'nullable|numeric|min:0',
            'fees.deposit.percentage' => 'nullable|numeric|min:0',
            'fees.deposit.minimum' => 'nullable|numeric|min:0',
            'fees.withdrawal.fixed' => 'nullable|numeric|min:0',
            'fees.withdrawal.percentage' => 'nullable|numeric|min:0',
            'fees.withdrawal.minimum' => 'nullable|numeric|min:0',
        ]);

        $bank = Bank::create($data);

        if (isset($feesData['fees'])) {
            $bank->fees_config = $feesData['fees'];
            $bank->save();
        }

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

    // Em app/Http/Controllers/BankController.php

    public function update(Request $request, Bank $bank)
    {
        // Validação dos dados principais do banco
        $bankData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'token' => 'nullable|string',
            'user' => 'nullable|string',
            'password' => 'nullable|string',
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
            'baseurl' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);


        $feesData = $request->validate([
            'fees' => 'nullable|array',
            'fees.deposit.fixed' => 'nullable|numeric|min:0',
            'fees.deposit.percentage' => 'nullable|numeric|min:0',
            'fees.deposit.minimum' => 'nullable|numeric|min:0',
            'fees.withdrawal.fixed' => 'nullable|numeric|min:0',
            'fees.withdrawal.percentage' => 'nullable|numeric|min:0',
            'fees.withdrawal.minimum' => 'nullable|numeric|min:0',
        ]);

        // Remove a senha do array se ela não foi alterada
        if (!$request->filled('password')) {
            unset($bankData['password']);
        }

        // Atualiza os dados principais do banco
        $bank->update($bankData);


        if (isset($feesData['fees'])) {
            $bank->fees_config = $feesData['fees'];
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
