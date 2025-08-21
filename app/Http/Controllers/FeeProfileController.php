<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FeeProfile;
use Illuminate\Http\Request;

class FeeProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profiles = FeeProfile::with('accounts')->latest()->get();
        return view('fee-profiles.index', compact('profiles'));
    }

    /**
     * Show the form for creating a new resource. 
     */
    public function create()
    {
        $accounts = Account::all(); // Para o dropdown de contas
        return view('fee-profiles.create', compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'calculation_type' => 'required|string|in:SIMPLE_FIXED,GREATER_OF_BASE_PERCENTAGE,TIERED',
            'fixed_fee' => 'nullable|numeric',
            'base_fee' => 'nullable|numeric',
            'percentage_fee' => 'nullable|numeric',
            'account_id' => 'nullable|exists:accounts,id',
        ]);

        FeeProfile::create($validatedData);

        return redirect()->route('fee-profiles.index')->with('success', 'Perfil de taxa criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(FeeProfile $feeProfile) // Route model binding em ação!
    {
        $accounts = Account::all();
        return view('fee-profiles.edit', compact('feeProfile', 'accounts'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeeProfile $feeProfile)
    {
        $validatedData = $request->validate([/* ... mesmas regras do store ... */]);
        $feeProfile->update($validatedData);
        return redirect()->route('fee-profiles.index')->with('success', 'Perfil de taxa atualizado com sucesso.');
    }

    // Deletar um perfil
    public function destroy(FeeProfile $feeProfile)
    {
        $feeProfile->delete();
        return redirect()->route('fee-profiles.index')->with('success', 'Perfil de taxa removido com sucesso.');
    }
}
