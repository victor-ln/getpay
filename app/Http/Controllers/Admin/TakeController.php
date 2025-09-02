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

class TakeController extends Controller
{
    /**
     * Show the form for creating a new Take.
     * This method calculates the profit and shows the preview screen.
     */
    public function create()
    {
        // 1. Encontrar a data de corte (o final do último take bem-sucedido)
        $lastTakeDate = PlatformTake::where('payout_status', 'completed')
                                    ->latest('end_date')
                                    ->first()?->end_date ?? '1970-01-01'; // Data inicial se nunca houve um take

        // 2. Buscar todos os pagamentos (IN e OUT) não carimbados desde a data de corte
        $pendingPayments = Payment::whereNull('take_id')
                                  ->where('status', 'paid')
                                  ->where('created_at', '>', $lastTakeDate)
                                  ->get();

        // 3. Calcular o lucro total (fee - cost)
        // Assumindo que você tem as colunas 'fee' e 'cost' na sua tabela 'payments'
        $totalProfit = $pendingPayments->sum(function ($payment) {
            return ($payment->fee ?? 0) - ($payment->cost ?? 0);
        });

        // 4. Buscar as contas de origem (seus bancos) e os destinos
        $sourceBanks = Bank::where('is_active', true)->get();
        $destinations = PayoutDestination::where('is_active', true)->get();

        // 5. Retornar a view com todos os dados calculados
        return view('admin.takes.create', [
            'totalProfit' => $totalProfit,
            'startDate' => $lastTakeDate,
            'endDate' => now(), // A data atual
            'sourceBanks' => $sourceBanks,
            'destinations' => $destinations,
            'reportData' => [], // TODO: Gerar o relatório detalhado por cliente
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
                'report_data' => "{}", // TODO: Adicionar o relatório
            ]);

            // 2. "Carimbar" os pagamentos
            $paymentIds = $pendingPayments->pluck('id');
            Payment::whereIn('id', $paymentIds)->update(['take_id' => $take->id]);

            // 3. TODO: EXECUTAR O PAYOUT VIA API DO BANCO
            // $bank = Bank::find($validated['source_bank_id']);
            // $destination = PayoutDestination::find($validated['destination_payout_key_id']);
            // try {
            //     // Chamar seu serviço de pagamento:
            //     // $response = PayoutService::send($bank, $destination, $totalProfit);
            //     // $take->update(['payout_status' => 'completed', 'payout_provider_transaction_id' => $response->id]);
            // } catch (\Exception $e) {
            //     // $take->update(['payout_status' => 'failed', 'payout_failure_reason' => $e->getMessage()]);
            // }
        });

        return redirect()->route('admin.takes.history.index') // Precisaremos criar esta rota/página de histórico
                         ->with('success', 'Take processing initiated!');
    }
}