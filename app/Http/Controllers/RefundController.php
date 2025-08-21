<?php

namespace App\Http\Controllers;

use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundController extends Controller
{

    protected $refundService;

    // ✅ 2. Injete o service no construtor
    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }
    /**
     * Exibe a página de gerenciamento de reembolsos.
     */
    public function index(Request $request)
    {

        $user = Auth::user();


        $data = $this->refundService->getRefundDashboardData($request->all(), $user);


        $data['payments'] = $data['refundablePayments'] ?? $data['payments'];

        return view('refunds.index', $data);
    }
}
