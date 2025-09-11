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

    public function index()
    {
        // 1. Busca os Takes já realizados, do mais novo para o mais antigo, com paginação.
        $takes = PlatformTake::latest('end_date')->paginate(15);


        $kpis = [
            'profit_this_month' => PlatformTake::whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('total_net_profit'),
            'takes_this_month' => PlatformTake::whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
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
        // [MELHORIA] Usa a mesma lógica do 'store' para a data de início, para consistência.
        $startDate = PlatformTake::where('payout_status', 'paid')
            ->latest('end_date')->first()?->end_date ?? '1970-01-01';
        $endDate = now();

        // Busca todos os pagamentos pendentes
        $pendingPayments = Payment::whereNull('take_id')
            ->where('status', 'paid')
            ->where('created_at', '>', $startDate)
            ->get();

        // Calcula o relatório detalhado por conta de cliente (para a tabela)
        $reportData = $pendingPayments->groupBy('account_id')->map(function ($payments) {
            return (object)[ // Retorna como objeto para facilitar o acesso na view
                'account_name' => $payments->first()->account->name ?? 'Conta Apagada',
                'total_in'     => $payments->where('type_transaction', 'IN')->sum('amount'),
                'total_out'    => $payments->where('type_transaction', 'OUT')->sum('amount'),
                'total_profit' => $payments->sum(fn($p) => ($p->fee ?? 0) - ($p->cost ?? 0)),
            ];
        })->values();

        // ✅ [A GRANDE MUDANÇA] Calcula o lucro agrupado por cada banco/adquirente
        $payoutsByAcquirer = $pendingPayments->groupBy('acquirer_id')->map(function ($payments, $acquirerId) {
            return (object)[
                'acquirer_id'   => $acquirerId,
                'acquirer_name' => $payments->first()->bank->name ?? 'Adquirente Desconhecido', // Assumindo relacionamento 'bank' no model Payment
                'profit'        => $payments->sum(fn($p) => ($p->fee ?? 0) - ($p->cost ?? 0)),
            ];
        })->values();

        // Calcula o lucro total para o card principal
        $totalProfit = $payoutsByAcquirer->sum('profit');

        // Busca os destinos de saque
        $destinations = \App\Models\PayoutDestination::where('is_active', true)->get();

        return view('admin.takes.create', [
            'totalProfit'        => $totalProfit,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'destinations'       => $destinations,
            'reportData'         => $reportData,
            'payoutsByAcquirer'  => $payoutsByAcquirer, // <-- Envia os dados agrupados para a view
        ]);
    }

    /**
     * Store a newly created Take in storage and execute the payout.
     */
    public function store(Request $request)
    {
        // Validação para um array de payouts
        $validated = $request->validate([
            'payouts' => 'required|array',
            'payouts.*.source_bank_id' => 'required|exists:banks,id',
            'payouts.*.destination_id' => 'required|exists:payout_destinations,id',
            'payouts.*.amount' => 'required|numeric|gt:0',
        ]);

        // Recalcular os dados para segurança (sua lógica atual já é ótima)
        $lastTakeDate = PlatformTake::where('payout_status', 'completed')->latest('end_date')->first()?->end_date ?? '1970-01-01';
        $pendingPayments = Payment::whereNull('take_id')->where('status', 'paid')->where('created_at', '>', $lastTakeDate)->get();

        if ($pendingPayments->isEmpty()) {
            return back()->with('error', 'No new transactions to process.');
        }

        DB::transaction(function () use ($validated, $pendingPayments) {
            // 1. Criar o registro do Take (a parte contábil)
            $take = PlatformTake::create([
                // ... preenche os dados do take como antes (total_profit, report_data, etc)
            ]);

            // 2. "Carimbar" os pagamentos
            Payment::whereIn('id', $pendingPayments->pluck('id'))->update(['take_id' => $take->id]);

            // 3. ✅ [A GRANDE MUDANÇA] Disparar os múltiplos saques
            foreach ($validated['payouts'] as $payoutData) {
                $bank = Bank::find($payoutData['source_bank_id']);
                $destination = PayoutDestination::find($payoutData['destination_id']);
                $amount = $payoutData['amount'];

                // O ideal aqui é despachar um JOB para a fila, para não travar a tela
                // PayoutJob::dispatch($take, $bank, $destination, $amount);

                // Mas, para uma solução rápida, a chamada direta ao serviço funcionaria:
                try {
                    // PayoutService::send($bank, $destination, $amount);
                } catch (\Exception $e) {
                    // Logar a falha de um saque específico
                    Log::error("Falha no saque do Take #{$take->id} para o banco {$bank->name}: " . $e->getMessage());
                    // Você pode decidir se quer continuar os outros ou reverter tudo
                }
            }
        });

        return redirect()->route('admin.takes.index') // Rota de histórico
            ->with('success', 'Take processing initiated for all acquirers!');
    }
}
