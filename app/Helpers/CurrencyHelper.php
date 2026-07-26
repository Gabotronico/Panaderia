<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Formatear cantidad a bolivianos
     */
    public static function format($amount)
    {
        return 'Bs ' . number_format($amount, 2, ',', '.');
    }

    /**
     * Símbolo de la moneda
     */
    public static function symbol()
    {
        return 'Bs';
    }

    /**
     * Código de la moneda
     */
    public static function code()
    {
        return 'BOB';
    }
}