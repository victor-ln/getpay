<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FunctionsController extends Controller
{
    //
    function converterValor($valor)
    {
        // Remove espaços e formatações
        $valor = trim(str_replace(',', '.', $valor));

        // Se for inteiro, adiciona ".00" pra garantir consistência
        if (strpos($valor, '.') === false) {
            $valor .= '.00';
        }

        // Multiplica por 100 para converter para centavos
        $resultado = floatval($valor) * 100;

        // Retorna como inteiro
        return (int) round($resultado);
    }

    function converterParaDecimal($valor)
    {
        // Garante que é inteiro
        $valor = (int) $valor;

        // Divide por 100 para converter centavos para reais
        $resultado = $valor / 100;

        // Formata com duas casas decimais
        return number_format($resultado, 2, '.', '');
    }
}
