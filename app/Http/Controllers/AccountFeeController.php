<?php

namespace App\Http\Controllers;

use App\Models\AccountFee;
use Illuminate\Http\Request;

class AccountFeeController extends Controller
{
    public function storeAccountFee(Request $request)
    {



        $resp = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'fee_id' => 'required|exists:fees,id',
        ]);

        if ($request->type == null || $request->type == '') {
            $is_default = 1;
        }


        AccountFee::create([
            'account_id' => $request->account_id,
            'fee_id' => $request->fee_id,
            'type' => $request->type,
            'status' => '1'
        ]);


        return response()->json(['success' => true, 'message' => 'Fee assigned successfully']);
    }
}
