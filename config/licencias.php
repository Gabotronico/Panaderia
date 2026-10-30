<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Licencias de panaderías
    |--------------------------------------------------------------------------
    |
    | El panel central de INC Group es la fuente de verdad: manda el estado de
    | cada licencia y acá solo se aplica. El secreto firma esa llamada (HMAC
    | SHA-256); sin él, el endpoint interno queda cerrado.
    |
    */

    'secreto' => env('LICENCIA_SECRETO'),

    // Segundos de tolerancia para el timestamp de la firma (relojes).
    'tolerancia' => (int) env('LICENCIA_TOLERANCIA', 300),

    /*
    | Planes comerciales. Son los mismos códigos que usa el panel central; acá
    | quedan como respaldo por si llega un plan sin cupos explícitos. null es
    | "sin límite".
    */
    'planes' => [
        'basico' => ['nombre' => 'Básico', 'admins' => 1, 'cajeros' => 2],
        'estandar' => ['nombre' => 'Estándar', 'admins' => 1, 'cajeros' => 4],
        'pro' => ['nombre' => 'Pro', 'admins' => 2, 'cajeros' => 6],
        'ilimitado' => ['nombre' => 'Ilimitado', 'admins' => null, 'cajeros' => null],
    ],

    /*
    | Qué rol consume qué cupo. Los roles que no figuran acá no tienen tope.
    */
    'roles' => [
        'Administrador' => 'max_admins',
        'Cajero' => 'max_cajeros',
    ],
];
