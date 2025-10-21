<?php


namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidPhone implements Rule
{
    public $cleanPhone;
    public $formattedPhone;

    public function passes($attribute, $value)
    {
        // Remove todos os caracteres não numéricos
        $this->cleanPhone = preg_replace('/[^0-9]/', '', $value);

        // Valida se tem 10 dígitos (fixo) ou 11 dígitos (celular)
        // Formato: DDD (2 dígitos) + Número (8 ou 9 dígitos)
        $isValid = strlen($this->cleanPhone) === 10 || strlen($this->cleanPhone) === 11;

        if ($isValid) {
            // Formata com +55 para enviar à liquidante
            $this->formattedPhone = '+55' . $this->cleanPhone;
        }

        return $isValid;
    }

    public function message()
    {
        return 'The PHONE number must contain more than 10 digits, including the area code.';
    }
}
