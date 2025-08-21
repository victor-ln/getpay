<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function listClients()
    {
        // Garante que apenas um admin pode acessar esta lista
        if (Auth::user()->level !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Busca todos os usuários que são 'client' e retorna apenas id e nome
        $clients = User::where('level', 'client')->get(['id', 'name']);

        return response()->json($clients);
    }
}
