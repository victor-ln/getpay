<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountFeeController;
use App\Http\Controllers\AccountPixKeyController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserProfileTwoFactorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PartnerPayoutController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserFeeController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\FeeProfileController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerPayoutMethodController;
use App\Http\Controllers\PixKeyController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\WithdrawController;
use App\Models\Account;
use App\Models\AccountPixKey;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});


Route::get('/', function () {
    return redirect('login');
});

Route::get('/register', function () {
    return redirect('login');
});


Route::get('/partner/dashboard', [PartnerController::class, 'dashboard'])
    ->name('partner.dashboard')
    ->middleware('auth');

Route::get('/accounts/{account}/history', [PartnerController::class, 'showAccountHistory'])->name('accounts.history');

Route::get('/login', [AuthWebController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
// Route::get('/register', [AuthWebController::class, 'registerForm'])->name('register');
// Route::post('/register', [AuthWebController::class, 'register']);

// Rota para enviar o link de redefinição de senha
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);




Route::middleware('auth')->group(function () {
    Route::post('/partner-payout-methods', [PartnerPayoutMethodController::class, 'store'])->name('partner-payout-methods.store');
    Route::delete('/partner-payout-methods/{payoutMethod}', [PartnerPayoutMethodController::class, 'destroy'])->name('partner-payout-methods.destroy');
    Route::put('/partner-payout-methods/{payoutMethod}/set-default', [PartnerPayoutMethodController::class, 'setDefault'])->name('partner-payout-methods.setDefault');
});


Route::middleware(['auth'])->group(function () {

    Route::get('/admin/clients', [AdminController::class, 'listClients']);
    Route::get('/dashboard-data', [ApiDashboardController::class, 'getDashboardData']);
    Route::post('/create-payment', [PaymentController::class, 'processPayment']);
    Route::post('/request-payout', [WithdrawController::class, 'processWithdrawal']);
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'requestRefund'])->name('payments.refund');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'showReceipt'])->name('payments.receipt');
    Route::patch('/user/update-document', [UserController::class, 'updateDocument'])->name('user.update-document');
    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::get('/payments/{payment}/download', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt.download');


    // Rota para associar um sócio a uma conta
    Route::post('/accounts/{account}/partners', [AccountController::class, 'attachPartner'])->name('accounts.partners.attach');

    // Rota para desvincular um sócio de uma conta
    Route::delete('/accounts/{account}/partners/{partner}', [AccountController::class, 'detachPartner'])->name('accounts.partners.detach');

    Route::prefix('account-pix-keys')->name('account-pix-keys.')->group(function () {
        // Rota para salvar uma nova chave (POST)
        Route::post('/', [AccountPixKeyController::class, 'store'])->name('store');

        // Rota para deletar uma chave existente (DELETE)
        Route::delete('/{pixKey}', [AccountPixKeyController::class, 'destroy'])->name('destroy');
    });







    Route::prefix('user/two-factor-authentication')->name('user.2fa.')->group(function () {
        // Mostrar o formulário de configuração do 2FA (QR Code, etc.)
        Route::get('/', [UserProfileTwoFactorController::class, 'showSetupForm'])->name('setup');

        // Confirmar e habilitar o 2FA após escanear o QR Code e inserir o primeiro código
        Route::post('/confirm', [UserProfileTwoFactorController::class, 'confirmTwoFactor'])->name('confirm');

        // Habilitar o 2FA (gera novo segredo e mostra QR - usado se o usuário cancelou a confirmação anterior)
        Route::post('/enable', [UserProfileTwoFactorController::class, 'enableTwoFactorAuthSetup'])->name('enable');

        // Desabilitar o 2FA
        Route::post('/disable', [UserProfileTwoFactorController::class, 'disableTwoFactor'])->name('disable');

        // Mostrar/Regerar códigos de recuperação (opcionalmente, pode ser parte do 'setup' se já habilitado)
        Route::get('/recovery-codes', [UserProfileTwoFactorController::class, 'showRecoveryCodes'])->name('recovery-codes');
        Route::post('/recovery-codes', [UserProfileTwoFactorController::class, 'generateRecoveryCodes'])->name('generate-recovery-codes'); // Para regerar
    });
});


// Rota protegida
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metrics', [DashboardController::class, 'getMetrics'])->name('dashboard.metrics');
    Route::post('/dashboard/select-account', [DashboardController::class, 'selectAccount'])->name('dashboard.select-account');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');


    Route::get('/transactions', [PaymentController::class, 'index'])->name('transactions.index');

    Route::resource('/partners', PartnerPayoutController::class)->middleware('check.level:admin,manager');


    Route::post('/accounts/{account}/webhooks', [WebhookController::class, 'store'])->name('accounts.webhooks.store');
    // Rota para regenerar o token de um webhook
    Route::put('/webhooks/{webhook}/regenerate', [WebhookController::class, 'regenerate'])->name('webhooks.regenerate');
    // Rota para deletar um webhook específico
    Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');


    // Rota para adicionar um usuário existente ou novo a uma conta
    Route::post('/accounts/{account}/users', [AccountController::class, 'addUser'])->name('accounts.users.add');


    Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');

    // User Fee Management Routes
    Route::post('user-fees', [UserFeeController::class, 'storeUserFee'])->middleware('check.level:admin,manager');
    Route::post('account-fees', [AccountFeeController::class, 'storeAccountFee'])->middleware('check.level:admin,manager');

    // Fee routes
    Route::resource('fees', FeeController::class)->middleware('check.level:admin,manager');
    Route::resource('logs', LogController::class)->middleware('check.level:admin,manager');

    Route::resource('accounts', AccountController::class);
    Route::delete('/accounts/{account}/users/{user}', [AccountController::class, 'detachUser'])->name('accounts.users.detach')->middleware('auth');


    Route::get('users', [UserController::class, 'index'])->name('users');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create')->middleware('check.level:admin,manager');
    Route::get('users/edit/{user}', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{id}', [UserController::class, 'update'])->name('users.update');

    Route::delete('users/{id}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('check.level:admin,manager');
    Route::post('users/store', [UserController::class, 'store'])->name('users.store')->middleware('check.level:admin,manager');

    Route::patch('fees/{fee}/toggle-active', [FeeController::class, 'toggleActive'])->name('fees.toggle-active')->middleware('check.level:admin,manager');

    Route::resource('/banks', BankController::class)->names('banks')->middleware('check.level:admin,manager');
    Route::patch('/banks/{bank}/activate', [BankController::class, 'activate'])->name('banks.activate')->middleware('check.level:admin,manager');


    // Rota para logout
    Route::get('/logout', [AuthWebController::class, 'logout'])->name('logout');


    //rotas do novo modelo de fee
    Route::resource('fee-profiles', FeeProfileController::class);

    // ROTA PARA ADICIONAR UM PERFIL A UMA CONTA
    Route::post('/accounts/{account}/fee-profiles', [AccountController::class, 'attachFeeProfile'])->name('accounts.fee-profiles.attach');

    // ROTA PARA REMOVER UM PERFIL DE UMA CONTA
    Route::delete('/accounts/{account}/fee-profiles/{feeProfile}', [AccountController::class, 'detachFeeProfile'])->name('accounts.fee-profiles.detach');
});
// Rota pública para o login
