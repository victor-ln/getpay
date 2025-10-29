<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Resources\PaymentResource;

class PaymentController extends Controller
{
    /**
     * Verifica e retorna o status de uma transação específica.
     * A busca pode ser feita por 'external_payment_id' ou 'provider_transaction_id'.
     */
    public function getStatus(Request $request)
    {
        // 1. Valida a requisição
        $validated = $request->validate([
            'id' => 'required|string'
        ]);

        $identifier = $validated['id'];

        // 2. Verifica se o usuário está autenticado ANTES de usar
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 3. Busca a conta do usuário
        $account = $user->accounts()->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        // 4. Executa a "Busca Inteligente"
        $payment = Payment::where('account_id', $account->id)
            ->where(function ($query) use ($identifier) {
                $query->where('external_payment_id', $identifier)
                    ->orWhere('provider_transaction_id', $identifier);
            })
            ->first();

        // 5. Retorna a resposta
        if (!$payment) {
            return response()->json(['message' => 'Transaction not found in this account.'], 404);
        }

        return response()->json([
            'success' => true,
            'externalId' => $payment->external_payment_id,
            'providerTransactionId' => $payment->provider_transaction_id,
            'status' => $payment->status,
            'type' => $payment->type_transaction,
            'amount' => $payment->amount,
        ]);
    }

    /**
     * Retorna o valor total (amount) e a taxa (fee) total das transações
     * dentro de um período, filtrado por tipo de transação.
     *
     * Se nenhum tipo de transação for fornecido, o padrão será "IN".
     */
    public function calculateTotals(Request $request)
    {
        // 1. Validação dos dados de entrada
        $validated = $request->validate([
            'date' => 'nullable|date',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'type_transaction' => 'nullable|string|in:IN,OUT',
        ]);

        $user = Auth::user();
        $accountId = $user->accounts()->first()->id;

        if (!$accountId) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        // 3. Constrói a query base
        $query = Payment::where('account_id', $accountId);
        $query->where('status', 'paid');

        // 4. Aplica os filtros de data
        if (isset($validated['date'])) {
            $date = Carbon::parse($validated['date']);
            $query->whereDate('created_at', $date); // Ignora a parte da hora
        } elseif (isset($validated['startDate'])) {
            $startDate = Carbon::parse($validated['startDate'])->startOfDay();
            $endDate = isset($validated['endDate'])
                ? Carbon::parse($validated['endDate'])->endOfDay()
                : $startDate->copy()->endOfDay(); // Se só tem startDate, vai até o final do dia
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // 5. Filtra por tipo de transação (padrão: "IN")
        $typeTransaction = $validated['type_transaction'] ?? 'IN';
        $query->where('type_transaction', $typeTransaction);

        // 6. Calcula os totais
        $totals = $query->selectRaw('SUM(amount) as total_amount, SUM(fee) as total_fee')->first();

        // 7. Retorna os resultados
        return response()->json([
            'total_amount' => (float) ($totals->total_amount ?? 0), // Garante que retorna 0 se for null
            'total_fee' => (float) ($totals->total_fee ?? 0),
            'currency' => 'BRL', //TODO pegar a moeda da conta
        ]);
    }



    public function filter(Request $request)
    {
        // 1. Valida os filtros recebidos. Todos são opcionais.
        $validated = $request->validate([
            'status' => 'nullable|string|in:paid,processing,pending,refunded,canceled',
            'type_transaction' => 'nullable|string|in:IN,OUT',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $user = Auth::user();
        $accountId = $user->accounts()->first()->id;

        if (!$accountId) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        // 3. Constrói a query dinamicamente
        $query = Payment::where('account_id', $accountId);

        // Adiciona o filtro de status, se fornecido
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Adiciona o filtro de tipo de transação, se fornecido
        if (isset($validated['type_transaction'])) {
            $query->where('type_transaction', $validated['type_transaction']);
        }

        // Adiciona o filtro de range de datas, se fornecido
        if (isset($validated['date_from']) && isset($validated['date_to'])) {
            // Carbon::parse é inteligente e entende os formatos 'd-m-Y' e 'Y-m-d'
            $dateFrom = Carbon::parse($validated['date_from'])->startOfDay();
            $dateTo = Carbon::parse($validated['date_to'])->endOfDay();
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        // 4. Executa a consulta com ordenação e paginação
        $paginatedPayments = $query->orderBy('created_at', 'desc')->paginate(15);

        return PaymentResource::collection($paginatedPayments);
    }
}
