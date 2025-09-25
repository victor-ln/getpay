<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutDestinationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Busca todos os destinos cadastrados no banco de dados
        $destinations = PayoutDestination::latest()->get();

        // Retorna a view (que criaremos a seguir) e passa a variável com os destinos
        return view('admin.payout-destinations.index', compact('destinations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Apenas retorna a view com o formulário de criação
        return view('admin.payout-destinations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validação dos dados que vêm do formulário
        $validatedData = $request->validate([
            'nickname' => 'required|string|max:255',
            'pix_key_type' => 'required|string',
            'pix_key' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_document' => 'required|string|max:255',
        ]);

        // Cria um novo registro no banco com os dados validados
        PayoutDestination::create($validatedData);

        return redirect()->route('admin.payout-destinations.index')
            ->with('success', 'PIX Key destination created successfully!');
    }

    public function setDefaultTake(PayoutDestination $payout_destination)
    {
        try {
            // Usamos uma transação para garantir que a operação seja "tudo ou nada"
            DB::transaction(function () use ($payout_destination) {
                // 1. Primeiro, remove a bandeira de "padrão" de todas as outras chaves
                PayoutDestination::where('id', '!=', $payout_destination->id)
                    ->update(['is_default_for_takeouts' => false]);

                // 2. Depois, define a bandeira como 'true' apenas para a chave selecionada
                $payout_destination->update(['is_default_for_takeouts' => true]);
            });

            return back()->with('success', 'The default pix key for "Takes" has been updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while trying to update the default pix key.');
        }
    }
}
