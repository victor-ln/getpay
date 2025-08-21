<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Traits\ToastTrait;
use App\Models\UserFee;

class UserFeeController extends Controller
{
    /**
     * Exibe a lista de usuários para gerenciar taxas
     */
    public function index() {}

    public function storeUserFee(Request $request)
    {



        $resp = $request->validate([
            'user_id' => 'required|exists:users,id',
            'fee_id' => 'required|exists:fees,id',
            'is_default' => 'boolean'
        ]);

        if ($request->type == null || $request->type == '') {
            $is_default = 1;
        }


        UserFee::create([
            'user_id' => $request->user_id,
            'fee_id' => $request->fee_id,
            'is_default' => $is_default ?? 0,
            'type' => $request->type,
            'status' => '1'
        ]);


        return response()->json(['success' => true, 'message' => 'Fee assigned successfully']);
    }

    /**
     * Exibe o formulário para gerenciar taxas de um usuário específico
     */
    public function edit(User $user) {}

    /**
     * Atualiza as taxas associadas a um usuário
     */
    public function update(Request $request, User $user) {}
}
