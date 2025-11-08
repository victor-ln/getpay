<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Payment;
use App\Models\PayoutDestination;
use App\Models\PlatformTake;
use App\Jobs\ProcessTakePayoutJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class TakeController extends Controller
{

    public function index()
    {
        // 1. Busca os Takes já realizados, do mais novo para o mais antigo, com paginação.
        $takes = PlatformTake::latest('end_date')->paginate(15);


        $kpis = [
            'profit_this_month' => PlatformTake::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_net_profit'),
            'takes_this_month' => PlatformTake::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // 3. Envia os dados para a view
        return view('admin.takes.index', [
            'takes' => $takes,
            'kpis' => $kpis
        ]);
    }


    public function show(PlatformTake $take)
    {

        return view('admin.takes.show', [
            'take' => $take
        ]);
    }
    /**
     * Show the form for creating a new Take.
     * This method calculates the profit and shows the preview screen.
     */
    public function create()
    {
        // 1. Obter a data de início (Sua lógica está ótima)
        $lastTakeDateString = PlatformTake::where('payout_status', 'paid')
            ->latest('end_date')->first()?->end_date ?? '1970-01-01';

        $startDate = Carbon::parse($lastTakeDateString);
        $endDate = now();

        // 2. Criar a consulta base
        // Esta é a base que usaremos para todas as agregações.
        // Note que NÃO usamos ->get()
        $pendingPaymentsQuery = Payment::whereNull('take_id')
            ->where('payments.status', 'paid')
            ->where('payments.updated_at', '>', $startDate);

        // 3. CALCULAR $reportData (DIRETO NO BANCO)
        // Isso substitui o seu primeiro ->get()->groupBy()->map()
        $reportData = (clone $pendingPaymentsQuery) // Clonamos a query base
            ->join('accounts', 'payments.account_id', '=', 'accounts.id')
            ->select(
                'accounts.name as account_name',
                // Usamos DB::raw() para funções de agregação do SQL
                DB::raw("SUM(CASE WHEN payments.type_transaction = 'IN' THEN payments.amount ELSE 0 END) as total_in"),
                DB::raw("SUM(COALESCE(payments.fee, 0)) as total_fee"),
                DB::raw("SUM(COALESCE(payments.cost, 0)) as total_cost")
            )
            ->groupBy('accounts.name')
            ->get(); // Este ->get() agora só retorna os TOTAIS (poucas linhas)

        // 4. CALCULAR $payoutsByAcquirer (DIRETO NO BANCO)
        // Isso substitui o seu segundo ->groupBy()->map()
        // Assumindo que provider_id se relaciona com a tabela banks (como no seu código)
        $payoutsByAcquirer = (clone $pendingPaymentsQuery) // Clonamos a query base
            ->join('banks', 'payments.provider_id', '=', 'banks.id')
            ->select(
                'payments.provider_id as acquirer_id',
                'banks.name as acquirer_name',
                // COALESCE trata valores NULL (caso fee/cost sejam nulos)
                DB::raw('SUM(COALESCE(payments.fee, 0) - COALESCE(payments.cost, 0)) as profit')
            )
            ->groupBy('payments.provider_id', 'banks.name')
            ->get(); // Este ->get() também só retorna os TOTAIS

        // 5. O resto da sua lógica
        $totalProfit = $payoutsByAcquirer->sum('profit');
        $destinations = PayoutDestination::where('is_active', true)->get();

        // 6. Retornar a View
        return view('admin.takes.create', [
            'totalProfit'        => $totalProfit,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'destinations'       => $destinations,
            'reportData'         => $reportData,
            'payoutsByAcquirer'  => $payoutsByAcquirer,
        ]);
    }

    /**
     * Store a newly created Take in storage and execute the payout.
     */
    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'payouts' => 'required|array',
            'payouts.*.source_bank_id' => 'required|exists:banks,id',
            'payouts.*.destination_id' => 'required|exists:payout_destinations,id',
            'payouts.*.amount' => 'required|numeric|gt:0',
        ]);

        
        $lastTakeDate = PlatformTake::where('payout_status', 'paid')->latest('end_date')->first()?->end_date ?? '1970-01-01';
        

        $pendingPayments = Payment::whereNull('take_id')
                            ->where('status', 'paid')
                            ->where('created_at', '>', $lastTakeDate)
                            ->exists();

        if ($pendingPayments->isEmpty()) {
            return back()->with('error', 'No new transactions to process.');
        }

        $take = null;

        
            $reportData = $this->generateDetailedReportFromDB($lastTakeDate);

          
            $summary = $this->calculateSummaryFromDB($lastTakeDate);

        DB::transaction(function () use ($validated, $pendingPayments, &$take, $summary, $reportData) {
            
            
             // $summary = $this->calculateSummary($pendingPayments);
             //$reportData = $this->generateDetailedReport($pendingPayments);
            
            $take = PlatformTake::create([
                'start_date'        => $pendingPayments->min('created_at'),
                'end_date'          => now(),
                'total_net_profit'  => $summary['total_net_profit'],
                'total_volume_in'   => $summary['total_volume_in'],
                'total_volume_out'  => $summary['total_volume_out'],
                'total_fees_in'     => $summary['total_fees_in'],
                'total_fees_out'    => $summary['total_fees_out'],
                'total_costs_in'    => $summary['total_costs_in'],
                'total_costs_out'   => $summary['total_costs_out'],
                'report_data'       => $reportData,
                'payout_status'     => 'paid', 
                'executed_by_user_id' => Auth::id(),
            ]);

            
            Payment::whereIn('id', $pendingPayments->pluck('id'))->update(['take_id' => $take->id]);

            
            foreach ($validated['payouts'] as $payoutData) {
                
                ProcessTakePayoutJob::dispatch(
                    $take->id,
                    $payoutData['source_bank_id'],
                    $payoutData['destination_id'],
                    $payoutData['amount']
                );
            }
        });

        
        return redirect()->route('admin.takes.index')
            ->with('success', "Take #{$take->id} successfully created! Withdrawals are being processed in the background mode.");
    }

    
    private function generateDetailedReport($payments)
    {
        return $payments->groupBy('account_id')->map(function ($paymentsByAccount) {
            return [
                'account_name' => $paymentsByAccount->first()->account->name ?? 'Conta Apagada',
                'total_in'     => $paymentsByAccount->where('type_transaction', 'IN')->sum('amount'),
                'total_fee'    => $paymentsByAccount->sum('fee'),
                'total_cost'   => $paymentsByAccount->sum('cost'),
            ];
        })->values();
    }

    private function calculateSummary($payments)
    {
        return [
            'total_net_profit'  => $payments->sum('platform_profit'),
            'total_volume_in'   => $payments->where('type_transaction', 'IN')->sum('amount'),
            'total_volume_out'  => $payments->where('type_transaction', 'OUT')->sum('amount'),
            'total_fees_in'     => $payments->where('type_transaction', 'IN')->sum('fee'),
            'total_fees_out'    => $payments->where('type_transaction', 'OUT')->sum('fee'),
            'total_costs_in'    => $payments->where('type_transaction', 'IN')->sum('cost'),
            'total_costs_out'   => $payments->where('type_transaction', 'OUT')->sum('cost'),
        ];
    }


    private function generateDetailedReportFromDB(string $lastTakeDate)
{
    
    
    $totalInSql = "SUM(CASE WHEN payments.type_transaction = 'IN' THEN payments.amount ELSE 0 END)";

    
    
    $accountNameSql = "COALESCE(accounts.name, 'Conta Apagada')";

    return Payment::query()
        
        ->whereNull('payments.take_id')
        ->where('payments.status', 'paid')
        ->where('payments.created_at', '>', $lastTakeDate)
        
        
        
        ->leftJoin('accounts', 'payments.account_id', '=', 'accounts.id')
        
        
        ->selectRaw("
            payments.account_id,
            $accountNameSql as account_name,
            $totalInSql as total_in,
            SUM(payments.fee) as total_fee,
            SUM(payments.cost) as total_cost
        ")
        
        
        ->groupBy('payments.account_id', 'accounts.name')
        
        
        ->get();
}

private function calculateSummaryFromDB(string $lastTakeDate)
{
    
    $volumeInSql  = "SUM(CASE WHEN type_transaction = 'IN' THEN amount ELSE 0 END)";
    $volumeOutSql = "SUM(CASE WHEN type_transaction = 'OUT' THEN amount ELSE 0 END)";

    
    $feesInSql    = "SUM(CASE WHEN type_transaction = 'IN' THEN fee ELSE 0 END)";
    $feesOutSql   = "SUM(CASE WHEN type_transaction = 'OUT' THEN fee ELSE 0 END)";

    
    $costsInSql   = "SUM(CASE WHEN type_transaction = 'IN' THEN cost ELSE 0 END)";
    $costsOutSql  = "SUM(CASE WHEN type_transaction = 'OUT' THEN cost ELSE 0 END)";

    return Payment::query()
        ->whereNull('take_id')
        ->where('status', 'paid')
        ->where('created_at', '>', $lastTakeDate)
        ->selectRaw("
            MIN(created_at) as start_date,
            SUM(platform_profit) as total_net_profit,
            
            $volumeInSql as total_volume_in,
            $volumeOutSql as total_volume_out,
            
            $feesInSql as total_fees_in,
            $feesOutSql as total_fees_out,
            
            $costsInSql as total_costs_in,
            $costsOutSql as total_costs_out
        ")
        ->first(); 
}
}
