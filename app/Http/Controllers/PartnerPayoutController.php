<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Services\PartnerPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // Importe a classe Rule para validação

class PartnerPayoutController extends Controller
{
    protected $payoutService;

    public function __construct(PartnerPayoutService $payoutService)
    {
        $this->payoutService = $payoutService;
    }

    /**
     * Exibe a página principal de organização de sócios.
     */
    public function index()
    {
        $viewData = $this->payoutService->getPayoutDashboardData();
        return view('partners.index', $viewData);
    }

    /**
     * ✅ REVISÃO: Armazena um novo sócio e retorna uma resposta JSON.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'pix_key' => 'required|string|max:255',
            'pix_key_type' => ['required', 'string', Rule::in(['cpf', 'cnpj', 'email', 'phone', 'random'])],
            'receiving_percentage' => 'required|numeric|min:0|max:100',
            'withdrawal_frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        if ($validator->fails()) {
            // Retorna os erros de validação como JSON com status 422
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $partner = Partner::create($validator->validated());

        // Retorna o novo sócio criado e uma mensagem de sucesso
        return response()->json([
            'success' => true,
            'message' => 'Partner added successfully!',
            'partner' => $partner
        ], 201); // 201 Created
    }

    /**
     * ✅ REVISÃO: Atualiza um sócio existente e retorna uma resposta JSON.
     */
    public function update(Request $request, Partner $partner)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'pix_key' => 'required|string|max:255',
            'pix_key_type' => ['required', 'string', Rule::in(['cpf', 'cnpj', 'email', 'phone', 'random'])],
            'receiving_percentage' => 'required|numeric|min:0|max:100',
            'withdrawal_frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $partner->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Partner updated successfully!',
            'partner' => $partner
        ]);
    }

    /**
     * ✅ REVISÃO: Desativa um sócio (soft delete) e retorna uma resposta JSON.
     */
    public function destroy(Partner $partner)
    {
        // Opcional: Adicionar uma Policy para autorização
        // $this->authorize('delete', $partner);

        $partner->is_active = false;
        $partner->save();

        // Soft delete, se você tiver a coluna 'deleted_at' e o Trait no model
        // $partner->delete(); 

        return response()->json([
            'success' => true,
            'message' => 'Partner deactivated successfully!'
        ]);
    }

    // Os métodos abaixo não são mais usados no fluxo AJAX, mas podem ser mantidos se você tiver outros usos para eles.
    public function create()
    {
        return redirect()->route('partners.index');
    }
    public function show(Partner $partner)
    {
        return response()->json($partner);
    }
    public function edit(Partner $partner)
    {
        return redirect()->route('partners.index');
    }
}
