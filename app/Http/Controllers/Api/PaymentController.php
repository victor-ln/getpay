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
        // 1. Valida a requisição para garantir que o identificador foi enviado.
        $validated = $request->validate([
            'id' => 'required|string'
        ]);

        $identifier = $validated['id'];
        $user = Auth::user();

        $accountId = $user->accounts()->first()->id;

        if (!$accountId) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        // 3. Executa a "Busca Inteligente"
        $payment = Payment::where('account_id', $accountId)
            ->where(function ($query) use ($identifier) {
                // Procura o identificador em QUALQUER uma das duas colunas
                $query->where('external_payment_id', $identifier)
                    ->orWhere('provider_transaction_id', $identifier);
            })
            ->first();

        // 4. Retorna a resposta
        if (!$payment) {
            return response()->json(['message' => 'Transaction not found in this account.'], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $payment->status,
            'type' => $payment->type_transaction,
            'amount' => $payment->amount,
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
