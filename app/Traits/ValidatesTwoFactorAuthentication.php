<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

trait ValidatesTwoFactorAuthentication
{
    /**
     * Verifica o código 2FA de um usuário, testando códigos de recuperação e TOTP.
     * Lança uma exceção em caso de falha.
     *
     * @param User $user
     * @param string|null $tfaCode
     * @param string $actionContext Usado para os logs (ex: 'WITHDRAW' ou 'REFUND')
     * @return void
     * @throws \Exception
     */
    protected function verifyTwoFactorCode(User $user, ?string $tfaCode, string $actionContext = 'ACTION'): void
    {
        if (empty($tfaCode)) {
            throw new \Exception('2FA code is required.');
        }

        // A propriedade $this->google2fa deve ser injetada no construtor da classe que usa este Trait.
        if (!property_exists($this, 'google2fa')) {
            throw new \Exception('Google2FA service not available in the class using this trait.');
        }

        $isValid = false;

        // --- Verificação do Código de Recuperação ---
        $recoveryCodes = [];
        if ($user->two_factor_recovery_codes) {
            try {
                $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (\Exception $e) {
                Log::warning('Could not decrypt recovery codes for user: ' . $user->id, ['error' => $e->getMessage()]);
                $recoveryCodes = [];
            }
        }

        $usedCodeIndex = null;
        foreach (($recoveryCodes ?? []) as $index => $codeData) {
            if (is_array($codeData) && empty($codeData['used_at']) && hash_equals((string) $codeData['code'], $tfaCode)) {
                $usedCodeIndex = $index;
                break;
            }
        }

        if ($usedCodeIndex !== null) {
            $recoveryCodes[$usedCodeIndex]['used_at'] = now()->toDateTimeString();
            $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
            $user->save();
            $isValid = true;
            $this->logAction($user, $actionContext . '_2FA_RECOVERY_USED');
        } else {
            // --- Verificação do Código do App (TOTP) ---
            try {
                $secret = decrypt($user->two_factor_secret);
                $isValid = $this->google2fa->verifyKey($secret, $tfaCode);
            } catch (\Exception $e) {
                Log::error('Could not decrypt 2FA secret for user: ' . $user->id, ['error' => $e->getMessage()]);
                $isValid = false;
            }
        }

        if (!$isValid) {
            $this->logAction($user, $actionContext . '_2FA_FAILED');
            throw new \Exception('The 2FA code is invalid.');
        }

        $this->logAction($user, $actionContext . '_2FA_SUCCESS');
    }
}
