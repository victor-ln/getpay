<?php

namespace App\Http\Controllers;

use App\Models\{Bank, Payment};
use App\Services\BankKpiService;
use Illuminate\Http\Request;
use App\Traits\ToastTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{

    use ToastTrait;

    protected $kpiService;

    public function __construct(BankKpiService $kpiService)
    {
        $this->kpiService = $kpiService;
    }
    public function index()
    {

        $banks =  Bank::all();
        return view('banks.index', compact('banks'));
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
