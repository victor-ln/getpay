<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\PaymentBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Para registrar erros

class PaymentBatchController extends Controller
{
    /**
     * Mostra o formulário para criar um novo lote de pagamentos.
     */
    public function create()
    {
        // Busque as liquidantes para o <select> do formulário
        // Ajuste 'Acquirer' se o seu model tiver outro nome
        $acquirers = Bank::where('is_active', true)->get(); // Exemplo

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
            'acquirer_id' => 'required|exists:acquirers,id', 
            'total_amount' => 'required|numeric|min:0.01',
            'number_of_splits' => 'required|integer|min:1',
        ]);

        

        
        $totalInCents = $validated['total_amount'] * 100;
        $splits = $validated['number_of_splits'];

        
        $splitAmountInCents = floor($totalInCents / $splits);
        $remainder = $totalInCents % $splits;

        
        
        try {
            $batch = null; 

            DB::transaction(function () use ($validated, $totalInCents, $splits, $splitAmountInCents, $remainder, &$batch) {
                
                
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

                    
                    $batch->transactions()->create([
                        'acquirer_id' => $batch->acquirer_id,
                        'amount' => $currentAmount,
                        'status' => 'pending', 
                        'payment_batch_id' => $batch->id,
                        'account_id' => $accountId ?? null,
                        'user_id' => Auth::id(),
                        'fee' => 0,
                        'type_transaction' => 'IN',
                        
                    ]);
                }
            });

            
            return redirect()->route('batches.create') 
                             ->with('success', 'Payment batch created successfully!');

        } catch (\Exception $e) {

            Log::error('Failed to create payment batch: ' . $e->getMessage());

            return back()->with('error', 'Failed to create payment batch. Please try again.')
                         ->withInput();
        }
    }
}