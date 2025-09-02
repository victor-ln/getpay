<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutDestination; // Model que criamos
use Illuminate\Http\Request;

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

    
}