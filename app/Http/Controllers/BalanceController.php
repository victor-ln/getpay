<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Garante que apenas administradores possam acessar esta página.
        // Supondo que você tenha um método isAdmin() no seu model User.
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized Access');
        }


        // $balances = Balance::where('available_balance', '>', 0)
        //     ->orWhere('blocked_balance', '>', 0)
        //     ->orderBy('updated_at', 'desc')
        //     ->paginate(20);

        $balances = Balance::orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('balances.index', compact('balances'));
    }
}
