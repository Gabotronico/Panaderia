<?php

use App\Helpers\CurrencyHelper;

if (!function_exists('currency')) {
    /**
     * Formatear moneda boliviana
     */
    function currency($amount)
    {
        return CurrencyHelper::format($amount);
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Obtener símbolo de moneda
     */
    function currency_symbol()
    {
        return CurrencyHelper::symbol();
    }
}