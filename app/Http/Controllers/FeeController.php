<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\User;
use App\Traits\ToastTrait;
use Illuminate\Http\Request;
use App\Helpers\FormatHelper;
use App\Models\Account;

class FeeController extends Controller
{


    use ToastTrait;
    /** 
     * Display a listing of the resource.
     */
    public function index()
    {

        $fees = Fee::get();

        $clients = Account::where('status', '1')
            ->get();

        return view('fees.index', compact('fees', 'clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $fee = null;
        return view('fees.edit', compact('fee'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->merge([
            'percentage' => FormatHelper::decimalToDatabase($request->input('percentage')),
            'minimum_fee' => FormatHelper::decimalToDatabase($request->input('minimum_fee')),
            'fixed_fee' => FormatHelper::decimalToDatabase($request->input('fixed_fee')),
        ]);

        // Depois valida
        $validated = $request->validate([
            'type' => 'required|in:IN,OUT',
            'percentage' => 'required|numeric|min:0',
            'minimum_fee' => 'required|numeric|min:0',
            'fixed_fee' => 'numeric|min:0',
        ]);

        // Cria a taxa
        $fee = Fee::create([
            'name' => 'Taxa Personalizada',
            'type' => $validated['type'],
            'percentage' => $validated['percentage'],
            'minimum_fee' => $validated['minimum_fee'],
            'fixed_fee' => $validated['fixed_fee'],
            'is_active' => '1',
        ]);

        // // Se for para associar a um usuário específico
        // if ($request->has('redirect_to_user') && $request->has('assign_to_user')) {
        //     $userId = $validated['redirect_to_user'];
        //     $user = User::findOrFail($userId);

        //     // Associar a taxa ao usuário
        //     $isDefault = $request->has('set_as_default');
        //     $user->fees()->attach($fee->id, ['is_default' => $isDefault]);

        //     // Redirecionar para a página de edição de taxas do usuário
        //     return redirect()->route('user_fees.edit', $user)
        //         ->with('success', 'Taxa personalizada criada e associada ao usuário com sucesso!');
        // }


        return $this->updatedSuccess('Fee created successfully!', 'fees.index');
    }

    /**
     * Display the specified resource. 
     */
    public function show(Fee $fee)
    {
        $fees = Fee::get();
        return $this->updatedSuccess('Fee updated successfully!', 'fees.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $fee = Fee::find($id);
        return view('fees.edit', compact('fee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fee $fee)
    {

        $request->merge([
            'percentage' => FormatHelper::decimalToDatabase($request->input('percentage')),
            'minimum_fee' => FormatHelper::decimalToDatabase($request->input('minimum_fee')),
            'fixed_fee' => FormatHelper::decimalToDatabase($request->input('fixed_fee')),
        ]);




        $validated = $request->validate([
            'type' => 'required|in:IN,OUT',
            'percentage' => 'required|numeric|min:0',
            'minimum_fee' => 'required|numeric|min:0',
        ]);

        $fee->update([
            'type' => $validated['type'],
            'percentage' => $validated['percentage'],
            'minimum_fee' => $validated['minimum_fee'],
            'fixed_fee' => $request['fixed_fee'],
        ]);

        return $this->updatedSuccess('Fee updated successfully!', 'fees.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fee $fee)
    {
        //$fee->userFees()->update(['status' => false]);
        $fee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fee deleted successfully!',
        ]);
    }

    /**
     * Toggle the active status of the fee.
     */
    public function toggleActive(Fee $fee)
    {



        $fee->update([
            'is_active' => !$fee->is_active,
        ]);

        return redirect()->route('fees.index')
            ->with('success', 'Status da taxa atualizado com sucesso!');
    }
}
