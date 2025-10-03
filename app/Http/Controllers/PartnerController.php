<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PartnerController extends Controller
{
    public function dashboard()
    {


        $loggedInPartner = Auth::user();

        if ($loggedInPartner->level == 'client') {
            return redirect()->route('/');
        }
        // --- DADOS GLOBAIS (PARA OS CARDS DO TOPO) ---

        // 1. Calcula o Saldo Total Custodiado somando todos os saldos de todas as contas
        $totalBalanceInCustody = \App\Models\Balance::whereNotIn('account_id', [5, 44])
            ->sum(DB::raw('available_balance + blocked_balance'));

        // 2. Calcula o Lucro Líquido Total (Fee - Cost) de todas as transações no período
        $lastSettlementPayment = \App\Models\PlatformTake::where('payout_status', 'paid')->latest('end_date')->first();



        // 2. Define a data de corte. Se nenhum pagamento for encontrado, usa uma data padrão (ex: o início do dia de hoje).
        $startDate = $lastSettlementPayment ? $lastSettlementPayment->created_at : now()->startOfDay();



        $totalNetProfit = \App\Models\Payment::where('created_at', '>=', $startDate)
            ->whereIn('status', ['paid', 'refunded'])
            // ✅ ADICIONADO: Garante que a soma do lucro total exclua as mesmas contas da lista.
            ->whereNotIn('account_id', [5, 44])
            ->sum(DB::raw('fee - cost'));







        // --- DADOS POR CONTA (PARA A LISTA DETALHADA) ---

        // 3. A nova query principal, começando por Account
        $accountSummariesQuery = \App\Models\Account::with([
            'balances', // Carrega o saldo
            'feeProfiles' => function ($query) { // Carrega as taxas ativas
                $query->where('status', 'active');
            },
            'acquirer'
        ])
            ->whereHas('payments', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            // Somas para transações IN
            ->withSum(['payments as total_in' => fn($q) => $q->where('type_transaction', 'IN')->where('status', 'paid')->where('created_at', '>=', $startDate)], 'amount')
            ->withSum(['payments as fee_in' => fn($q) => $q->where('type_transaction', 'IN')->whereIn('status', ['paid', 'refunded'])->where('created_at', '>=', $startDate)], 'fee')
            ->withSum(['payments as cost_in' => fn($q) => $q->where('type_transaction', 'IN')->whereIn('status', ['paid', 'refunded'])->where('created_at', '>=', $startDate)], 'cost')
            // Somas para transações OUT
            ->withSum(['payments as total_out' => fn($q) => $q->where('type_transaction', 'OUT')->where('status', 'paid')->where('created_at', '>=', $startDate)], 'amount')
            ->withSum(['payments as fee_out' => fn($q) => $q->where('type_transaction', 'OUT')->whereIn('status', ['paid', 'refunded'])->where('created_at', '>=', $startDate)], 'fee')
            ->withSum(['payments as cost_out' => fn($q) => $q->where('type_transaction', 'OUT')->whereIn('status', ['paid', 'refunded'])->where('created_at', '>=', $startDate)], 'cost');


        // 4. FILTRO DE TESTE: Pegar apenas uma conta específica.
        //    Para ver todas, simplesmente comente ou remova a linha abaixo.
        //$accountSummariesQuery->where('id', 26); 

        $accountSummariesQuery->whereNotIn('id', [5, 44]);

        $accountSummaries = $accountSummariesQuery->get();



        $banks = Bank::where('active', 1)->get();


        $banksWithBalance = [];

        // foreach ($banks as $bank) {
        //     $dados = $bank->toArray();
        //     $dados['token'] = $this->getToken($dados);

        //     $response = $this->getBalance($dados);

        //     $banksWithBalance[] = [
        //         'id' => $bank->id,
        //         'name' => $bank->name,
        //         'balance' => $response['data']['balance'] ?? 0,
        //         'balance_provisioned' => $response['data']['balanceProvisioned'] ?? 0,
        //         'currency' => $response['data']['currency'] ?? 'BRL',
        //         'status_code' => $response['statusCode'] ?? null
        //     ];
        // }


        return view('partners.dashboard', compact(
            'totalBalanceInCustody',
            'totalNetProfit',
            'accountSummaries',
            'banksWithBalance',
            'lastSettlementPayment'
        ));
    }

    public function showAccountHistory(Request $request, Account $account)
    {
        // NO FUTURO: Esta linha garantirá que um sócio só veja as contas dele.
        // $this->authorize('view', $account);

        // --- INÍCIO DA LÓGICA DE DADOS REAIS ---



        // 1. Definir o período do filtro de datas
        // Se não for passado na request, usa os últimos 30 dias como padrão.
        $startDate = $request->input('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : now()->subDays(30);
        $endDate = $request->input('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : now();

        // 2. Criar a query base para os pagamentos desta conta e neste período
        $paymentsQuery = Payment::where('account_id', $account->id)->whereBetween('created_at', [$startDate, $endDate]);

        // 3. Calcular os KPIs (Indicadores) usando a query base
        $stats = (object)[
            // Soma o 'amount' apenas de transações de ENTRADA (IN)
            'total_volume' => $paymentsQuery->clone()->where('type_transaction', 'IN')->sum('amount'),

            // Soma as taxas da plataforma
            'platform_fees' => $paymentsQuery->clone()->sum('fee'),

            // Soma os custos do gateway/banco
            'gateway_costs' => $paymentsQuery->clone()->sum('cost'),

            // Soma o lucro líquido já calculado por transação
            'net_profit' => $paymentsQuery->clone()->sum('platform_profit'),
        ];

        // 4. Buscar o histórico de pagamentos paginado para exibir na tabela
        $payments = $paymentsQuery->latest()->paginate(20)->withQueryString();

        // --- FIM DA LÓGICA DE DADOS REAIS ---





        // 5. Retornar a view com os dados reais
        return view('partners.history', [
            'account' => $account,
            'stats' => $stats,
            'payments' => $payments,
            // Envia as datas para preencher os filtros na view
            'filters' => ['date_from' => $startDate->format('Y-m-d'), 'date_to' => $endDate->format('Y-m-d')]
        ]);
    }

    public function getToken($dados)
    {
        try {


            $response = Http::withHeaders([
                'accept' => '*/*',
                'Content-Type' => 'application/json',
            ])
                ->withOptions([
                    'verify' => false
                ])
                ->post($dados['baseurl'] . 'GerarToken', [
                    'username' => $dados['user'],
                    'password' => $dados['password']
                ]);






            if ($response['jwt']) {
                return $response['jwt'];
            }



            Log::error('lumepay authentication error', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('lumepay authentication exception', [
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }


    public function getBalance($dados)
    {

        try {

            $response = Http::withToken($dados['token'])
                ->withOptions([
                    'verify' => false
                ])
                // Usar send() permite controlar o método e o corpo separadamente
                ->send('POST', $dados['baseurl'] . 'GetBalance', [
                    'json' => [
                        'username' => $dados['user'],
                        'password' => $dados['password'],
                    ]
                ]);





            return [
                'statusCode' => $response->status(),
                'data' => $response->json()['data']
            ];
        } catch (\Exception $e) {
            Log::error('Truzt create charge exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'statusCode' => 500,
                'data' => ['error' => $e->getMessage()],
                'acquirer' => 'truzt'
            ];
        }
    }
}
