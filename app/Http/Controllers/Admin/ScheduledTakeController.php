<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTake;
use App\Models\Bank;
use Illuminate\Http\Request;

class ScheduledTakeController extends Controller
{
    public function index()
    {
        $scheduledTakes = ScheduledTake::with('bank')->latest()->get();
        return view('admin.scheduled_takes.index', compact('scheduledTakes'));
    }

    public function create()
    {
        $banks = Bank::where('active', true)->orderBy('name')->get();
        return view('admin.scheduled_takes.create', compact('banks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_id' => 'required|exists:banks,id|unique:scheduled_takes,bank_id',
            'frequency' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = $request->has('is_active');

        ScheduledTake::create($validated);

        return redirect()->route('admin.scheduled-takes.index')->with('success', 'Agendamento criado com sucesso!');
    }

    public function destroy(ScheduledTake $scheduledTake)
    {
        $scheduledTake->delete();
        return redirect()->route('admin.scheduled-takes.index')->with('success', 'Agendamento removido com sucesso!');
    }

    public function toggle(ScheduledTake $scheduledTake)
    {
        // A lógica de atualização continua a mesma
        $scheduledTake->update([
            'is_active' => !$scheduledTake->is_active
        ]);

        // ✅ [CORREÇÃO] Removemos a condição e forçamos sempre uma resposta JSON.
        // Esta é a forma mais fiável de comunicar com o 'fetch' do JavaScript.
        return response()->json([
            'success' => true,
            'message' => 'Schedule status updated successfully!'
        ]);
    }
}
