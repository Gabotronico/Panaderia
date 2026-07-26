<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base de cálculo de nómina
    |--------------------------------------------------------------------------
    |
    | Estos valores definen cómo se convierte el salario base de un empleado
    | a su equivalente por día y por hora. Se asume semana laboral de lunes a
    | sábado (el domingo no se trabaja ni se contabiliza).
    |
    */

    // Días laborables por mes (lun–sáb ≈ 26)
    'dias_mes' => 26,

    // Días laborables por semana (lun–sáb = 6)
    'dias_semana' => 6,

    // Jornada estándar en horas
    'horas_jornada' => 8,

    /*
    |--------------------------------------------------------------------------
    | Tolerancia de tardanza
    |--------------------------------------------------------------------------
    |
    | Minutos de gracia antes de que una llegada tarde genere descuento.
    |
    */
    'tolerancia_tardanza' => 0,

];
