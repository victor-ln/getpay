<?php

namespace App\Http\Controllers;

use App\Services\WithdrawService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WithdrawController extends Controller
{
    protected $withdrawService;

    public function __construct(WithdrawService $withdrawService)
    {
        $this->withdrawService = $withdrawService;
    }

    /**
     * Processa uma solicitação de saque
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processWithdrawal(Request $request)
    {

        $user = Auth::user();

        $clientIp = $request->header('CF-Connecting-IP') ?? $request->ip();

        if ($request->is('api/*')) {

            $allowedIps = [
                '69.162.120.198',
                '2804:214:8678:cb24:1:0:2bdc:7e9e'
            ];


            $restrictedUserIds = [93, 73, 68, 67, 61, 62];

            if (in_array($user->id, $restrictedUserIds) && !in_array($clientIp, $allowedIps)) {
                // Log com o contexto correto
                $context = [
                    'detected_ip_by_logic' => $clientIp,
                    'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
                    'laravel_default_ip' => $request->ip(),
                    'remote_addr' => $request->server('REMOTE_ADDR'),
                ];
                event(new \App\Events\UserActionOccurred($user, 'WITHDRAWAL_FAILED_IP_MISMATCH', $context, 'Withdrawal attempt from a blocked IP.'));

                // Retorna a resposta de erro com o IP correto
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied from your IP address: ' . $clientIp
                ], 403);
            }
        }



        try {
            $user = Auth::user();

            $data = [
                'externalId'     => $request->input('externalId'),
                'pixKey'         => $request->input('pixKey'),
                'pixKeyType'     => $request->input('pixKeyType'), // Precisa vir do frontend
                'name'           => $user->name,
                'documentNumber' => $request->input('documentNumber'),
                'amount'         => $request->input('amount'),
            ];


            // O método $request->is('api/*') retorna true se a URL começar com 'api/'.
            $isApiRequest = $request->is('api/*');

            // A variável $skipTwoFactorCheck é definida dinamicamente.
            // Se for uma requisição de API, ela será 'true'. Se não, será 'false'.
            $skipTwoFactorCheck = $isApiRequest;

            Log::info("Request detected as " . ($isApiRequest ? "API" : "Web") . ". Skipping 2FA: " . ($skipTwoFactorCheck ? 'Yes' : 'No'));



            $result = $this->withdrawService->processWithdrawal($user, $request->all(), $skipTwoFactorCheck);

            if ($result['success']) {

                return response()->json([
                    'success' => true,
                    'message' => 'Withdraw processed successfully',
                    'data' => $result['data']
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null
            ], 400);
        } catch (\Exception $e) {
            Log::error('Erro no processamento do saque: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro ao processar sua solicitação de saque',
                'error' => $e->getMessage()
            ], 500);
        }


        // try {
        //     // Validar os dados da requisição
        //    

        //     // Obter usuário autenticado
        //     $user = Auth::user();

        //     // Calcular saldo disponível
        //     $balanceInfo = $this->withdrawService->calculateAvailableBalance($user->id);


        //     $withdrawalFee = $this->withdrawService->calculateWithdrawalFee($request->amount);

        //     // Verificar se o usuário tem saldo suficiente
        //     $totalWithdrawalAmount = $request->amount + $withdrawalFee;

        //     if ($balanceInfo['availableBalance'] < $totalWithdrawalAmount) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Insufficient balance',
        //             'data' => [
        //                 'availableBalance' => $balanceInfo['availableBalance'],
        //                 'requestedAmount' => $request->amount
        //             ]
        //         ], 400);
        //     }

        //     // Processar o saque através da Dubay
        //     $result = $this->withdrawService->processDubayWithdrawal($user, $request->all(), $withdrawalFee);

        //     if ($result['success']) {
        //         return response()->json($result);
        //     } else {
        //         return response()->json($result, 400);
        //     }
        // } catch (\Exception $e) {
        //     Log::error('Erro no processamento do saque: ' . $e->getMessage(), [
        //         'trace' => $e->getTraceAsString(),
        //         'request' => $request->all()
        //     ]);

        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Ocorreu um erro ao processar sua solicitação de saque',
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }
}
