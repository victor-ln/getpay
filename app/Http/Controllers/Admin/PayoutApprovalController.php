<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash; // Usaremos para o PIN no futuro
use Illuminate\Support\Facades\Auth; // Para verificar o PIN do admin

class PayoutApprovalController extends Controller
{
    /**
     * Mostra a lista de saques (OUT) pendentes.
     */
    public function index()
    {
        $pendingPayouts = Payment::where('type_transaction', 'OUT')
            ->where('status', 'pending')
            ->whereNotNull('name')
            ->with(['account', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.payout_approvals.index', compact('pendingPayouts'));
    }

    /**
     * Retorna os dados de um saque para o modal "View Details".
     */
    public function details(Payment $payment)
    {
        // Garante que é um Payout
        if ($payment->type_transaction !== 'OUT') {
            abort(404);
        }

        // Retorna os dados formatados que o seu modal precisa
        return response()->json([
            'id' => $payment->id,
            'amount' => 'R$ ' . number_format($payment->amount, 2, ',', '.'),
            'fee' => 'R$ ' . number_format($payment->fee, 2, ',', '.'),
            'total_debit' => 'R$ ' . number_format($payment->amount + $payment->fee, 2, ',', '.'),
            'destination_name' => $payment->name, // Nome do destinatário
            'destination_document' => $payment->document,
            'destination_key_type' => $payment->pix_key_type, // Assumindo que você tem esta coluna
            'destination_key' => $payment->pix_key,       // Assumindo que você tem esta coluna
            'requested_by_account' => $payment->account->name,
            'requested_at' => $payment->created_at->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Recebe a tentativa de aprovação com PIN.
     */
    public function approve(Request $request, Payment $payment)
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'The PIN must be 6 digits.'], 422);
        }

        // --- LÓGICA DE VERIFICAÇÃO DO PIN (Exemplo) ---
        // No futuro, você buscaria o PIN (com hash) do admin logado
        // $adminPinHash = Auth::user()->pin_hash;
        // if (Hash::check($request->pin, $adminPinHash)) { ... }

        // Por agora, para o teste, vamos simular um PIN correto e um incorreto
        if ($request->pin === '123456') {

            // Simula um pequeno atraso, como se estivesse a processar
            sleep(2);

            // (LÓGICA FUTURA)
            // Aqui você despacharia o Job para realmente executar o saque.
            // PayoutJob::dispatch($payment);
            $payment->update(['status' => 'paid']); // Atualiza o status

            return response()->json(['success' => true, 'message' => 'Payout approved and is being processed!']);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid PIN. Please try again.'], 401);
        }
    }

    /**
     * Cancela um saque pendente.
     */
    public function cancel(Request $request, Payment $payment)
    {
        // (LÓGICA FUTURA)
        // Aqui você chamaria o seu WithdrawService para reverter os fundos
        // $this->withdrawService->reverseBlockedFunds(...)

        $payment->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Payout has been cancelled.']);
    }
}
