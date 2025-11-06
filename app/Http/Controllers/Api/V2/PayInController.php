<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Account;
use App\Jobs\ProcessPayInJob; // Importa o Job que já criámos
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Rules\ValidDocument;
use Illuminate\Support\Facades\Log; // É uma boa prática usar o Facade

class PayInController extends Controller
{
    /**
     * Creates a new Pay In request asynchronously.
     */
    public function store(Request $request)
    {
        
        $account = $request->get('authenticated_account');

        if (!$account) {
            Log::error('Erro crítico: Conta autenticada pela API não encontrada na requisição.');
            
            return response()->json(['success' => false, 'message' => 'Authenticated account not found.'], 500);
        }

        
        $minAccount = $account->min_amount_transaction;
        $maxAccount = $account->max_amount_transaction;

        $effectiveMinAmount = max(0.01, (float) $minAccount);
        $effectiveMaxAmount = is_null($maxAccount) ? null : max(0.01, (float) $maxAccount);

        $documentRule = new ValidDocument();

        
        
        $validator = Validator::make($request->all(), [
            'externalId' => 'required|string', 
            'amount'     => 'required|numeric|min:' . $effectiveMinAmount . ($effectiveMaxAmount ? '|max:' . $effectiveMaxAmount : ''),
            'document'   => ['required', $documentRule],
            'name'       => 'required|string',
            'identification' => 'nullable|string',
            'expire'     => 'nullable|integer',
            'description'  => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }
        $validatedData = $validator->validated();

        

        
        try {
            $payment = Payment::create([
                'user_id'             => $account->id,
                'account_id'          => $account->id,
                'external_payment_id' => $validatedData['externalId'], 
                'amount'              => $validatedData['amount'],
                'fee'                 => 0,
                'cost'                => 0,
                'platform_profit'     => 0,
                'type_transaction'    => 'IN',
                'status'              => 'pending',
                'provider_id'         => $account->acquirer_id,
                'name'                => $validatedData['name'],
                'document'            => $validatedData['document'],
            ]);

            Log::info("Registo de Pay In pendente criado.", ['payment_id' => $payment->id, 'account_id' => $account->id]);

        } catch (QueryException $e) {
            
            $errorCode = $e->getCode();

            
            if ($errorCode == '23505') {
                Log::warning("Tentativa de criar pagamento com externalId duplicado.", [
                    'external_id' => $validatedData['externalId'],
                    'account_id'  => $account->id
                ]);
                
                
                return response()->json([
                    'success' => false,
                    'message' => 'A payment with this externalId already exists.',
                    'errors'  => [
                        'externalId' => ['The provided externalId is already in use.']
                    ]
                ], 409);
            }

            
            Log::error("Erro de banco de dados ao criar o registo de pagamento: " . $e->getMessage(), [
                'error_code' => $errorCode,
                'account_id' => $account->id
            ]);
            return response()->json(['message' => 'Failed to initiate payment creation due to a database error.'], 500);

        } catch (\Exception $e) {
            
            Log::error("Erro inesperado ao criar o registo de pagamento pendente: " . $e->getMessage(), ['account_id' => $account->id]);
            return response()->json(['message' => 'Failed to initiate payment creation due to an unexpected error.'], 500);
        }

        
        
        try {
            ProcessPayInJob::dispatch($payment->id, $validatedData);
            Log::info("ProcessPayInJob despachado para a fila.", ['payment_id' => $payment->id]);
        } catch (\Exception $e) {
            Log::error("Erro ao despachar ProcessPayInJob para a fila: " . $e->getMessage(), ['payment_id' => $payment->id]);
            
            
            $payment->update(['status' => 'failed', 'provider_response_data' => ['error' => 'Failed to dispatch processing job.']]);
            return response()->json(['message' => 'Failed to queue payment processing.'], 500);
        }

        
        
        return response()->json([
            'success'   => true,
            'message'   => 'Pay-in request received and is being processed.',
            'payment_id' => $payment->external_payment_id,
            'status'    => $payment->status,
        ], 202);
    }

   
}
