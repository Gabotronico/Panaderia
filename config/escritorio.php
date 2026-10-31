<?php

return [
    /*
    |--------------------------------------------------------------------------
    | App de escritorio: ventana hacia el VPS
    |--------------------------------------------------------------------------
    |
    | La app de escritorio ya no guarda datos locales: al abrir pide el código
    | de la panadería (una sola vez) y desde ahí carga el sistema del servidor.
    |
    */

    'url_base' => rtrim((string) env('ESCRITORIO_URL_BASE', 'https://obrador.incgroupbo.com'), '/'),

    // Archivo donde se recuerda el código elegido, dentro del storage local.
    'archivo' => 'escritorio-panaderia.txt',
];
