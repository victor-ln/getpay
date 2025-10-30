<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Account;
use App\Jobs\ProcessPayInJob; // Importa o Job que já criámos
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Rules\ValidDocument;

class PayInController extends Controller
{
    /**
     * Creates a new Pay In request asynchronously.
     */
    public function store(Request $request)
    {

        $account = $request->get('authenticated_account');


        if (!$account) {
            \Log::error('Erro crítico: Conta autenticada pela API não encontrada na requisição.');
            abort(500, 'Authenticated account not found.');
        }


        $minAccount = $account->min_amount_transaction;
        $maxAccount = $account->max_amount_transaction;






        $effectiveMinAmount = max(0.01, (float) $minAccount);
        $effectiveMaxAmount = is_null($maxAccount) ? null : max(0.01, (float) $maxAccount);

        $documentRule = new ValidDocument();


        $validator = Validator::make($request->all(), [
            'externalId' => 'required|string|unique:payments,external_payment_id',
            'amount' => 'required|numeric|min:' . $effectiveMinAmount . '|max:' . $effectiveMaxAmount,
            'document' => ['required', $documentRule],
            'name' => 'required|string',
            'identification' => 'nullable|string',
            'expire' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $validatedData = $validator->validated();

        // 2. Obtém a conta autenticada pelo Middleware
        // O middleware 'auth.api.client' já adicionou isto ao request.
        $account = $request->attributes->get('authenticated_account');
        if (!$account) {
            // Este erro não deveria acontecer se o middleware estiver correto
            return response()->json(['success' => false, 'message' => 'Authenticated account not found.'], 500);
        }

        // 3. Cria imediatamente o registo de pagamento com status 'pending'
        try {
            $payment = Payment::create([
                'user_id' => $account->id,
                'account_id' => $account->id,
                'external_payment_id' => $validatedData['externalId'] ?? 'payin_' . Str::uuid(),
                'amount' => $validatedData['amount'],
                'fee' => 0, // Será calculado depois
                'cost' => 0, // Será calculado depois
                'platform_profit' => 0, // Será calculado depois
                'type_transaction' => 'IN',
                'status' => 'pending',
                'provider_id' => $account->acquirer_id, // Usa o adquirente padrão da conta
                'name' => $validatedData['name'],
                'document' => $validatedData['document'],
            ]);

            \Log::info("Registo de Pay In pendente criado.", ['payment_id' => $payment->id, 'account_id' => $account->id]);
        } catch (\Exception $e) {
            \Log::error("Erro ao criar o registo de pagamento pendente: " . $e->getMessage(), ['account_id' => $account->id]);
            return response()->json(['message' => 'Failed to initiate payment creation.'], 500);
        }

        // 4. Despacha o Job para a fila para processar a criação da cobrança
        // Passamos o ID do pagamento e os dados validados que podem ser úteis
        try {
            ProcessPayInJob::dispatch($payment->id, $validatedData);
            \Log::info("ProcessPayInJob despachado para a fila.", ['payment_id' => $payment->id]);
        } catch (\Exception $e) {
            \Log::error("Erro ao despachar ProcessPayInJob para a fila: " . $e->getMessage(), ['payment_id' => $payment->id]);
            // Neste caso, o pagamento ficará 'pending' até que o robô de reconciliação o encontre,
            // ou podemos tentar reverter e marcar como 'failed'. Por agora, vamos apenas logar.
            $payment->update(['status' => 'failed', 'provider_response_data' => ['error' => 'Failed to dispatch processing job.']]);
            return response()->json(['message' => 'Failed to queue payment processing.'], 500);
        }

        // 5. Retorna uma resposta imediata (202 Accepted)
        return response()->json([
            'success' => true,
            'message' => 'Pay-in request received and is being processed.',
            'payment_id' => $payment->external_payment_id, // Retorna o ID interno para consulta futura
            'status' => $payment->status,
        ], 202);
    }

    // (Opcional) Poderia adicionar um método show(Payment $payment) aqui
    // para permitir que o cliente consulte o status do pagamento usando o ID.
}
