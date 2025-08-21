<?php

namespace App\Helpers;

class FormatHelper
{
    public static function decimalToDatabase($value)
    {
        // Se o valor for nulo ou vazio, retorna ele mesmo
        if (is_null($value) || $value === '') {
            return $value;
        }

        // Substitui a vírgula por ponto e remove espaços
        return str_replace(',', '.', trim($value));
    }
}
