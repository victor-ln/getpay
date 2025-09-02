<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank; // Seu model de Bancos
use App\Models\Payment; // Seu model de Pagamentos
use App\Models\PayoutDestination; // Nosso model de Destinos
use App\Models\PlatformTake; // O model da tabela platform_takes
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;  


class TakeController extends Controller
{
    /**
     * Show the form for creating a new Take.
     * This method calculates the profit and shows the preview screen.
     */
    public function create()
    {
        // --- 1. Definição das Datas (conforme seu pedido) ---
        $startDate = Carbon::yesterday()->setTime(17, 0, 0);
        $endDate = Carbon::today()->setTime(17, 0, 0);

        // --- 2. Busca e processa os dados de pagamentos ---
        $paymentsData = DB::table('payments')
            ->join('accounts', 'payments.account_id', '=', 'accounts.id')
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->where('payments.status', 'paid')
            ->whereNull('payments.take_id') // Apenas pagamentos não processados
            ->select(
                'accounts.name as account_name',
                DB::raw("SUM(CASE WHEN payments.type = 'IN' THEN payments.amount ELSE 0 END) as total_in"),
                DB::raw("SUM(CASE WHEN payments.type = 'OUT' THEN payments.amount ELSE 0 END) as total_out"),
                DB::raw("SUM(COALESCE(payments.fee, 0) - COALESCE(payments.cost, 0)) as total_profit")
            )
            ->groupBy('accounts.name')
            ->get();

        // --- 3. Calcula o lucro total ---
        $totalProfit = $paymentsData->sum('total_profit');

        // --- 4. Busca as contas de origem (seus bancos) e os destinos ---
        $sourceBanks = \App\Models\Bank::where('is_active', true)->get();
        $destinations = \App\Models\PayoutDestination::where('is_active', true)->get();

        // --- 5. Retorna a view com todos os dados ---
        return view('admin.takes.create', [
            'totalProfit' => $totalProfit,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sourceBanks' => $sourceBanks,
            'destinations' => $destinations,
            'reportData' => $paymentsData,
        ]);
    }

    /**
     * Store a newly created Take in storage and execute the payout.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_bank_id' => 'required|exists:banks,id',
            'destination_payout_key_id' => 'required|exists:payout_destinations,id',
        ]);

        // --- Lógica de execução ---
        // Recalcular tudo aqui dentro para garantir que nada foi manipulado no formulário
        $lastTakeDate = PlatformTake::where('payout_status', 'completed')->latest('end_date')->first()?->end_date ?? '1970-01-01';
        $pendingPayments = Payment::whereNull('take_id')->where('status', 'paid')->where('created_at', '>', $lastTakeDate)->get();

        if ($pendingPayments->isEmpty()) {
            return back()->with('error', 'No new transactions to process.');
        }

        $totalProfit = $pendingPayments->sum(fn($p) => ($p->fee ?? 0) - ($p->cost ?? 0));
        $startDate = $pendingPayments->min('created_at');
        $endDate = now();

        // Usar uma transação de banco de dados é uma boa prática
        DB::transaction(function () use ($validated, $totalProfit, $startDate, $endDate, $pendingPayments) {

            // 1. Criar o registro do Take (a parte contábil)
            $take = PlatformTake::create([
                'total_profit' => $totalProfit,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'source_bank_id' => $validated['source_bank_id'],
                'destination_payout_key_id' => $validated['destination_payout_key_id'],
                'executed_by_user_id' => Auth::id(),
                'payout_status' => 'processing',
                'report_data' => "{}", 
            ]);

            // 2. "Carimbar" os pagamentos
            $paymentIds = $pendingPayments->pluck('id');
            Payment::whereIn('id', $paymentIds)->update(['take_id' => $take->id]);

            
         $bank = Bank::find($validated['source_bank_id']);
             $destination = PayoutDestination::find($validated['destination_payout_key_id']);
             try {
                  
                  $response = PayoutService::send($bank, $destination, $totalProfit);
                  $take->update(['payout_status' => 'completed', 'payout_provider_transaction_id' => $response->id]);
             } catch (\Exception $e) {
                  $take->update(['payout_status' => 'failed', 'payout_failure_reason' => $e->getMessage()]);
             }
        });

        return redirect()->route('admin.takes.history.index') // Precisaremos criar esta rota/página de histórico
                         ->with('success', 'Take processing initiated!');
    }
}