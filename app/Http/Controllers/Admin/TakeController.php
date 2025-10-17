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
        // [CORREÇÃO] A lógica para obter a data de início foi ajustada para ser mais segura e consistente.
        // Usamos 'completed' para garantir que pegamos a data do último fecho de caixa bem-sucedido.
        $lastTakeDateString = PlatformTake::where('payout_status', 'paid')
            ->latest('end_date')->first()?->end_date ?? '1970-01-01';

        // ✅ A CORREÇÃO PRINCIPAL: Converte a string para um objeto Carbon
        $startDate = Carbon::parse($lastTakeDateString);
        $endDate = now(); // now() já retorna um objeto Carbon

        // O resto da sua lógica de busca e cálculo continua exatamente a mesma...
        $pendingPayments = Payment::whereNull('take_id')
            ->where('status', 'paid')
            ->where('updated_at', '>', $startDate)
            ->get();




        $reportData = $pendingPayments->groupBy('account_id')->map(function ($payments) {
            return (object)[
                'account_name' => $payments->first()->account->name ?? 'Conta ',
                'total_in'     => $payments->where('type_transaction', 'IN')->sum('amount'),
                'total_fee'    => $payments->sum('fee'), // ✅ NOVO: Soma de todas as taxas (IN e OUT)
                'total_cost'   => $payments->sum('cost'), // ✅ NOVO: Soma de todos os custos (IN e OUT)
            ];
        })->values();

        $payoutsByAcquirer = $pendingPayments->groupBy('provider_id')->map(function ($payments, $providerId) {
            return (object)[
                'acquirer_id'   => $providerId,
                // Agora que a relação 'bank' existe, esta linha irá funcionar
                'acquirer_name' => $payments->first()->bank->name ?? 'Adquirente Desconhecido',
                'profit'        => $payments->sum(fn($p) => ($p->fee ?? 0) - ($p->cost ?? 0)),
            ];
        })->values();

        $totalProfit = $payoutsByAcquirer->sum('profit');
        $destinations = \App\Models\PayoutDestination::where('is_active', true)->get();


        return view('admin.takes.create', [
            'totalProfit'        => $totalProfit,
            'startDate'          => $startDate, // Agora, esta variável é garantidamente um objeto Carbon
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
        // 1. Valida se o formulário enviou um array de saques com os campos necessários
        $validated = $request->validate([
            'payouts' => 'required|array',
            'payouts.*.source_bank_id' => 'required|exists:banks,id',
            'payouts.*.destination_id' => 'required|exists:payout_destinations,id',
            'payouts.*.amount' => 'required|numeric|gt:0',
        ]);

        // 2. Recalcula tudo para segurança, ignorando os valores do formulário (exceto os IDs)
        $lastTakeDate = PlatformTake::where('payout_status', 'paid')->latest('end_date')->first()?->end_date ?? '1970-01-01';
        $pendingPayments = Payment::whereNull('take_id')->where('status', 'paid')->where('created_at', '>', $lastTakeDate)->get();

        if ($pendingPayments->isEmpty()) {
            return back()->with('error', 'No new transactions to process.');
        }

        $take = null;

        DB::transaction(function () use ($validated, $pendingPayments, &$take) {
            // 3. Calcula os totais e o relatório detalhado
            $reportData = $this->generateDetailedReport($pendingPayments);
            $summary = $this->calculateSummary($pendingPayments);

            // 4. Cria o registo do Take na base de dados
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
                'payout_status'     => 'paid', // O status inicial é "a processar"
                'executed_by_user_id' => Auth::id(),
            ]);

            // 5. "Carimba" os pagamentos para que não sejam processados novamente
            Payment::whereIn('id', $pendingPayments->pluck('id'))->update(['take_id' => $take->id]);

            // 6. Despacha um Job para cada saque solicitado
            foreach ($validated['payouts'] as $payoutData) {
                // Despachamos para a fila, passando os IDs. O Job fará o resto.
                ProcessTakePayoutJob::dispatch(
                    $take->id,
                    $payoutData['source_bank_id'],
                    $payoutData['destination_id'],
                    $payoutData['amount']
                );
            }
        });

        // 7. Redireciona o utilizador para a página de histórico com uma mensagem de sucesso
        return redirect()->route('admin.takes.index')
            ->with('success', "Take #{$take->id} successfully created! Withdrawals are being processed in the background mode.");
    }

    // Métodos auxiliares para manter o código limpo
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
}
