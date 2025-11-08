<?php

namespace App\Http\Controllers;

use App\Jobs\SubmitPaymentToAcquirerJob;
use App\Models\Bank;
use App\Models\PaymentBatch;
use App\Models\User;
use App\Rules\ValidDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Str;

class PaymentBatchController extends Controller
{

    public function index()
    {
        $batches = PaymentBatch::where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        return view('batches.index', [
            'batches' => $batches,
        ]);
    }
    /**
     * Mostra o formulário para criar um novo lote de pagamentos.
     */
    public function create()
    {
        
        
        $acquirers = Bank::where('active', true)->get(); 

        return view('batches.create', [
            'acquirers' => $acquirers,
        ]);
    }

    /**
     * Armazena um novo lote de pagamentos e divide as transações.
     */
    public function store(Request $request)
    {
        
        $validated = $request->validate([
            
            'acquirer_id' => 'required',
            'total_amount' => 'required|numeric|min:0.01',
            'number_of_splits' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'document' => ['required', new ValidDocument()], 
            'description' => 'nullable|string|max:255',
        ]);

        
        $totalInCents = $validated['total_amount'] ;
        $splits = $validated['number_of_splits'];
        $documentClean = preg_replace('/[^0-9]/', '', (string) $validated['document']);

        
        $splitAmountInCents = floor($totalInCents / $splits);
        $remainder = $totalInCents % $splits;

        
        try {
            $batch = null; 

            DB::transaction(function () use ($validated, $totalInCents, $splits, $splitAmountInCents, $remainder, $documentClean, &$batch) {
                
                
                $batch = PaymentBatch::create([
                    'user_id' => Auth::id(),
                    'acquirer_id' => $validated['acquirer_id'],
                    'total_amount' => $totalInCents,
                    'number_of_splits' => $splits,
                    'status' => 'pending',
                ]);

                
                for ($i = 0; $i < $splits; $i++) {
                    
                    $currentAmount = $splitAmountInCents;
                    if ($i === ($splits - 1)) { 
                        $currentAmount += $remainder;
                    }

                    $account = User::find(Auth::id())->accounts()->first();
                    $payment = $batch->transactions()->create([
                        'user_id' => $batch->user_id,
                        'provider_id' => $batch->acquirer_id,
                        'amount' => $currentAmount,
                        'status' => 'pending', 
                        'type_transaction' => 'IN', 
                        'fee' => 0,
                        'account_id' => $account->id,
                        
                        
                        'external_payment_id' => 'B-' . uniqid() . '-' . $i, 
                        'name' => $validated['name'],
                        'document' => $documentClean,
                        'description' => $validated['description'],
                        
                        
                    ]);
                    
                    
                    SubmitPaymentToAcquirerJob::dispatch($payment);
                }
            });

            return redirect()->route('batches.show', ['batch' => $batch->id])
                     ->with('success', 'Batch created! Payments are being processed...');


        } catch (\Exception $e) {
            
            Log::error('Falha ao criar lote de pagamento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error creating batch: ' . $e->getMessage())
                         ->withInput(); 
        }
    }

    public function show(PaymentBatch $batch)
    {
        // Carrega o lote E todos os seus pagamentos (filhos)
        // A função 'payments' é a que definimos no Model PaymentBatch
        $batch->load('transactions'); 

        

        return view('batches.show', [
            'batch' => $batch
        ]);
    }
}