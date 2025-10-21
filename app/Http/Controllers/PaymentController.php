<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // Para fazer requisições HTTP
use Illuminate\Support\Str;
use App\Exports\TransactionsExport; // Importe sua classe de exportação
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {

        $perPage = 10;

        if (Auth::user()->level == 'admin') {

            $transactions = Payment::latest()->paginate($perPage);
        } else {

            $transactions = Payment::where('user_id', Auth::user()->id)
                ->latest()
                ->paginate($perPage);
        }

        // Inicia a query base, carregando o usuário para evitar N+1 problemas
        // $query = Payment::with('user')->latest(); // 'latest()' ordena por created_at DESC

        // // Aplica filtros com base nos parâmetros da request

        // if ($request->filled('filter_external_id')) {
        //     $query->where('external_payment_id', 'like', '%' . $request->input('filter_external_id') . '%');
        // }
        // if ($request->filled('filter_client_name')) {
        //     // Filtra pelo nome do cliente através da relação 'user'
        //     $query->whereHas('user', function ($q) use ($request) {
        //         $q->where('name', 'like', '%' . $request->input('filter_client_name') . '%');
        //     });
        // }
        // if ($request->filled('filter_type')) {
        //     $query->where('type_transaction', $request->input('filter_type'));
        // }
        // if ($request->filled('filter_status')) {
        //     $query->where('status', $request->input('filter_status'));
        // }

        // // Filtro de data de início
        // if ($request->filled('filter_start_date')) {
        //     try {
        //         $startDate = Carbon::createFromFormat('Y-m-d', $request->input('filter_start_date'))->startOfDay();
        //         $query->where('created_at', '>=', $startDate);
        //     } catch (\Exception $e) {
        //         Log::warning('Formato de data inválido para filter_start_date: ' . $request->input('filter_start_date'));
        //         // Opcional: retornar um erro para o usuário ou simplesmente ignorar o filtro de data inválido
        //     }
        // }

        // // Filtro de data de fim
        // if ($request->filled('filter_end_date')) {
        //     try {
        //         $endDate = Carbon::createFromFormat('Y-m-d', $request->input('filter_end_date'))->endOfDay();
        //         $query->where('created_at', '<=', $endDate);
        //     } catch (\Exception $e) {
        //         Log::warning('Formato de data inválido para filter_end_date: ' . $request->input('filter_end_date'));
        //     }
        // }

        // Verifica se a requisição é para exportar para XLS
        // if ($request->input('export') === 'xls') {
        //     // Verifica se a classe de exportação existe para evitar erros
        //     if (!class_exists(TransactionsExport::class)) {
        //         Log::error('A classe TransactionsExport não foi encontrada. Crie-a com `php artisan make:export TransactionsExport`.');
        //         return back()->with('error', 'A funcionalidade de exportação XLS não está configurada corretamente (classe de exportação ausente).');
        //     }
        //     if (!class_exists(Excel::class)) {
        //         Log::error('Maatwebsite/Excel não está instalado ou a facade não foi encontrada. Instale com `composer require maatwebsite/excel`.');
        //         return back()->with('error', 'A funcionalidade de exportação XLS não está configurada corretamente (biblioteca Excel ausente).');
        //     }
        //     // Gera um nome de arquivo único com data e hora
        //     $fileName = 'transactions_' . now()->format('Ymd_His') . '.xlsx';
        //     return Excel::download(new TransactionsExport($query), $fileName);
        // }




        return view('payments.index', compact('transactions'));
    }

    /** 
     * Process a new payment
     */
    public function processPayment(Request $request)
    {

        $user = Auth::user();

        $clientIp = $request->header('CF-Connecting-IP') ?? $request->ip();

        if ($request->is('api/*')) {

            $allowedIps = [
                '69.162.120.198',
                '2804:214:8678:cb24:1:0:2bdc:7e9e'
            ];


            $restrictedUserIds = [93, 73, 68, 67, 61, 62, 72];

            if (in_array($user->id, $restrictedUserIds) && !in_array($clientIp, $allowedIps)) {
                // Log com o contexto correto
                $context = [
                    'detected_ip_by_logic' => $clientIp,
                    'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
                    'laravel_default_ip' => $request->ip(),
                    'remote_addr' => $request->server('REMOTE_ADDR'),
                ];
                event(new \App\Events\UserActionOccurred($user, 'DEPOSIT_FAILED_IP_MISMATCH', $context, 'Deposit attempt from a blocked IP.'));

                // Retorna a resposta de erro com o IP correto
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied from your IP address: ' . $clientIp
                ], 403);
            }
        }



        try {

            $result = $this->paymentService->processPayment($request->all());


            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'data' => $result['data']
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null
            ], 400);
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the payment'
            ], 500);
        }
    }



    public function requestRefund(Request $request, Payment $payment)
    {
        // 1. Autorização: Garante que o usuário logado pode realizar esta ação.
        // O Laravel pode fazer isso automaticamente com Policies. Por agora, uma verificação simples:
        // if ($request->user()->cannot('refund', $payment)) { // 'refund' seria um método na sua PaymentPolicy
        //     abort(403, 'Unauthorized action.');
        // }
        $user = User::find($payment->user_id);

        if (!$user) {
            return response()->json(['message' => 'User associated with this payment not found.'], 404);
        }

        // 2. Chama o Service para fazer o trabalho pesado
        $tfaCode = $request->input('tfa_code');
        $result = $this->paymentService->processRefund($payment, $tfaCode);

        // 3. Retorna a resposta para o frontend
        if ($result['success']) {
            return response()->json(['message' => $result['message']]);
        }
        return response()->json(['message' => $result['message']], 400); // Bad Request
    }

    public function requestRefundApi(Request $request)
    {


        $payment = Payment::where('provider_transaction_id', $request['uuid'])
            ->orWhere('external_payment_id', $request['uuid'])
            ->first();



        if (!$payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        if ($payment->status != 'paid') {
            return response()->json(['message' => 'Payment not eligible for refund', 'status' => $payment->status], 300);
        }


        $user = User::find($payment->user_id);






        if (!$user) {
            return response()->json(['message' => 'User associated with this payment not found.'], 404);
        }

        // 2. Chama o Service para fazer o trabalho pesado
        $tfaCode = $request->input('tfa_code') ?? null;
        $result = $this->paymentService->processRefund($payment, $tfaCode);

        // 3. Retorna a resposta para o frontend
        if ($result['success']) {
            return response()->json(['message' => $result['message']]);
        }
        return response()->json(['message' => $result['message']], 400); // Bad Request
    }




    public function verifyTransaction(Request $request)
    {
        $result = $this->paymentService->verifyPayment($request->all());

        $dados =  response()->json($result);
        return $dados;
    }

    public function showReceipt(Payment $payment)
    {
        // Garante que o usuário logado só possa ver suas próprias transações
        if (auth()->id() !== $payment->user_id && auth()->user()->level != 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // $document = $payment->user->document->getMaskedDocumentAttribute;
        $document = $payment->document;

        $providerData = json_decode($payment->provider_response_data, true);
        $receiverDocument = $providerData['data']['metadata']['receiverDocument'] ?? $providerData['object']['receiver']['cpfCnpj'] ?? null;


        // Retorna os dados formatados. No futuro, podemos usar um API Resource aqui.
        return response()->json([
            'success' => true,
            'receipt' => [
                'transaction_id' => $payment->provider_transaction_id,
                'end_to_end_id' => $payment->end_to_end_id,
                'amount' => number_format($payment->amount, 2, ',', '.'),
                'status' => ucfirst($payment->status),
                'status_class' => $payment->status_class, // Usando o accessor que já temos!
                'date' => $payment->created_at->format('d/m/Y'),
                'time' => $payment->created_at->format('H:i:s'),
                'description' => $payment->description,
                'payer' => [
                    'name' => $providerData['data']['metadata']['payerName'] ?? $providerData['object']['payer']['name'] ?? 'GetPay ',
                ],
                'receiver' => [ // Dados de quem recebeu
                    'name' => $providerData['data']['metadata']['receiverName'] ?? $payment->name,
                    'document' => $this->maskDocument($receiverDocument), // Lembre-se de mascarar isso se necessário
                ],
            ]
        ]);
    }

    public function maskDocument($document)
    {
        if (!$document) return '***';

        // Remove caracteres não numéricos
        $doc = preg_replace('/\D/', '', $document);

        if (strlen($doc) == 11) { // CPF
            return '***.' . substr($doc, 3, 3) . '.' . substr($doc, 6, 3) . '-**';
        } elseif (strlen($doc) == 14) { // CNPJ
            return '**.' . substr($doc, 2, 3) . '.' . substr($doc, 5, 3) . '/****-**';
        }

        // Se não for CPF nem CNPJ, retorna mascarado genérico
        return '***' . substr($doc, 3, -3) . '***';
    }



    public function downloadReceipt(Payment $payment)
    {
        // Autorização (opcional, mas recomendado)
        if (auth()->id() !== $payment->user_id && auth()->user()->level != 'admin') {
            abort(403);
        }

        // Carrega os dados para a view do PDF
        $data = ['payment' => $payment];

        // Gera o PDF a partir da view Blade
        $pdf = Pdf::loadView('receipts.pdf', $data);

        // Define o nome do arquivo e força o download no navegador
        $fileName = 'receipt-' . $payment->id . '.pdf';
        return $pdf->download($fileName);
    }
}
