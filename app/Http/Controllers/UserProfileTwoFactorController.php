<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserProfileTwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    /**
     * ROTA: GET /user/two-factor-authentication (user.2fa.setup)
     * Mostra o estado atual do 2FA e, se o setup foi iniciado, exibe o QR Code.
     */
    public function showSetupForm(Request $request)
    {
        $user = $request->user();
        $qrCodeImage = null;
        $secretKeyForManualEntry = null;

        // Se um processo de habilitação foi iniciado, prepara o QR Code para a view.
        if ($unconfirmedSecretEncrypted = session('2fa_unconfirmed_secret')) {
            $secretKeyForManualEntry = Crypt::decryptString($unconfirmedSecretEncrypted);
            $qrCodeUrl = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secretKeyForManualEntry);
            $writer = new Writer(new ImageRenderer(new RendererStyle(250), new SvgImageBackEnd()));
            $qrCodeImage = $writer->writeString($qrCodeUrl);
        }

        return view('users.edit', compact('user', 'qrCodeImage', 'secretKeyForManualEntry'));
    }

    /**
     * Inicia o processo de habilitação do 2FA.
     */
    public function enableTwoFactorAuthSetup(Request $request)
    {
        $user = $request->user();

        Log::debug('Iniciando setup do 2FA para o usuário: ' . $user->id);

        if ($user->two_factor_secret) {
            Log::debug('Usuário já tem 2FA habilitado. Redirecionando.');
            return redirect()->route('user.2fa.setup')->with('error', '2FA is already enabled.');
        }
        $secret = $this->google2fa->generateSecretKey();
        Log::debug('Nova chave secreta 2FA gerada.');

        session(['2fa_unconfirmed_secret' => Crypt::encryptString($secret)]);
        Log::debug('Segredo temporário salvo na sessão com a chave [2fa_unconfirmed_secret].');

        if ($request->expectsJson()) {
            $qrCodeUrl = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);
            $writer = new Writer(new ImageRenderer(new RendererStyle(250), new SvgImageBackEnd()));

            return response()->json([
                'qrCodeSvg' => $writer->writeString($qrCodeUrl),
                'secretKey' => $secret,
            ]);
        }

        Log::debug('Redirecionando para a página de setup para exibir o QR Code.');
        return redirect()->route('user.2fa.setup');
    }

    /**
     * Confirma e ativa o 2FA.
     */
    public function confirmTwoFactor(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        Log::debug('Iniciando confirmação do 2FA para o usuário: ' . $user->id);

        $unconfirmedSecretEncrypted = session('2fa_unconfirmed_secret');
        if (!$unconfirmedSecretEncrypted) {
            Log::error('FALHA: Segredo não encontrado na sessão [2fa_unconfirmed_secret]. A sessão pode ter expirado.');
            return back()->withErrors(['code' => '2FA setup session expired. Please try again.'])->withErrorBag('confirmTwoFactor');
        }

        // Descriptografa o segredo da sessão para obter a chave em texto plano
        Log::debug('Segredo temporário encontrado na sessão. Descriptografando...');
        $secretPlainText = Crypt::decryptString($unconfirmedSecretEncrypted);

        $this->google2fa->setWindow(2);

        // Verifica se o código é válido usando a chave em texto plano
        if ($this->google2fa->verifyKey($secretPlainText, $request->input('code'))) {
            Log::debug('Código válido. Habilitando 2FA para o usuário.');
            // ✅ CORREÇÃO CRÍTICA:
            // Criptografa a chave em texto plano usando o helper 'encrypt()', que serializa primeiro.
            // Este é o formato que o 'decrypt()' no seu WithdrawService espera.
            $user->two_factor_secret = encrypt($secretPlainText);
            $user->two_factor_enabled = true;
            $recoveryCodes = $this->generateAndStoreRecoveryCodes($user);
            $user->save();

            session()->forget('2fa_unconfirmed_secret');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '2FA enabled successfully!',
                    'recoveryCodes' => $recoveryCodes
                ]);
            }

            return redirect()->route('user.2fa.setup')
                ->with('status', '2FA enabled successfully!')
                ->with('recovery_codes_to_show', $recoveryCodes);
        } else {
            Log::warning('Código 2FA fornecido pelo usuário é INVÁLIDO.');
        }

        return response()->json(['message' => 'Invalid 2FA code.'], 422);
    }

    /**
     * Desabilita o 2FA.
     */
    public function disableTwoFactor(Request $request)
    {
        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_enabled = false;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => '2FA disabled successfully.']);
        }
        return redirect()->route('user.2fa.setup')->with('status', '2FA disabled successfully.');
    }

    /**
     * Gera e armazena códigos de recuperação.
     */
    protected function generateAndStoreRecoveryCodes(User $user): array
    {
        $recoveryCodes = Collection::times(8, fn() => ['code' => Str::upper(Str::random(10)), 'used_at' => null])->all();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        return collect($recoveryCodes)->pluck('code')->all();
    }

    /**
     * ROTA: GET /user/two-factor-authentication/recovery-codes (user.2fa.recovery-codes)
     * Mostra os códigos de recuperação já existentes.
     */
    public function showRecoveryCodes(Request $request)
    {
        $user = $request->user();
        if (empty($user->two_factor_recovery_codes)) {
            return redirect()->route('user.2fa.setup')->with('error', 'No recovery codes found.');
        }

        $recoveryCodes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);
        $codesToShow = collect($recoveryCodes)->pluck('code')->all();

        return view('profile.show-recovery-codes', ['recoveryCodes' => $codesToShow]);
    }

    /**
     * ROTA: POST /user/two-factor-authentication/recovery-codes (user.2fa.generate-recovery-codes)
     * Gera NOVOS códigos de recuperação.
     */
    public function generateRecoveryCodes(Request $request)
    {
        $user = $request->user();
        if (!$user->two_factor_secret) {
            return redirect()->route('user.2fa.setup')->with('error', 'Enable 2FA before generating new recovery codes.');
        }

        $recoveryCodes = $this->generateAndStoreRecoveryCodes($user);
        $user->save();

        return redirect()->route('user.2fa.setup')
            ->with('status', 'New recovery codes generated!')
            ->with('recovery_codes_to_show', $recoveryCodes);
    }
}
