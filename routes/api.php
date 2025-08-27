<?php

use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\Api\PaymentController as ApiPaymentController;
use App\Http\Controllers\Webhook\DubaiWebhookController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware('auth:web')->get('/dashboard', [DashboardController::class, 'index']);



Route::post('/login', [AuthController::class, 'login'])->middleware('guest', 'throttle:logins');




Route::middleware(['auth:api', 'throttle:financials'])->group(function () {
    Route::post('/withdrawals', [WithdrawController::class, 'processWithdrawal']);
    Route::post('/create-payment', [PaymentController::class, 'processPayment']);
    Route::post('/refund', [PaymentController::class, 'requestRefundApi']);
    Route::post('/balance', [BalanceController::class, 'show']);
    Route::post('/transaction/status', [ApiPaymentController::class, 'getStatus']);
    Route::post('/transactions', [ApiPaymentController::class, 'filter']);
    Route::post('/transactions/totals', [ApiPaymentController::class, 'calculateTotals']);
});


// Route::post('/verify-payment', [PaymentController::class, 'verifyTransaction']);
// Route::post('/verify-hook', [WebhookController::class, 'handle']);
Route::post('/webhook/handler', [WebhookController::class, 'handleWebhook']);
Route::post('/webhook/resend', [WebhookController::class, 'resendWebhook']);
Route::post('/webhook/dubai', [DubaiWebhookController::class, 'handle']);


Route::get('/health', function () {
    // Tenta uma conexão simples com o banco para garantir que tudo está ok.
    try {
        DB::connection()->getPdo();
        return response()->json(['status' => 'healthy'], 200);
    } catch (\Exception $e) {
        // Se não conseguir conectar ao banco, retorna um erro 503 (Serviço Indisponível)
        return response()->json(['status' => 'unhealthy', 'database' => 'unreachable'], 503);
    }
});
