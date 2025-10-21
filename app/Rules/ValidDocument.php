<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidDocument implements Rule
{
    public $cleanDocument;

    public function passes($attribute, $value)
    {
        $this->cleanDocument = preg_replace('/[^0-9]/', '', $value);
        return strlen($this->cleanDocument) === 11 || strlen($this->cleanDocument) === 14;
    }

    public function message()
    {
        return 'The document must contain 11 digits (CPF) or 14 digits (CNPJ).';
    }
}
