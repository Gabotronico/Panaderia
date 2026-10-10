<?php

use Illuminate\Support\Facades\Route;

/*
| Rutas del dominio central (obrador.com). Se atan a cada dominio central
| para que no le ganen a las rutas de las panaderías, que comparten paths
| como "/" (ver routes/tenant.php).
*/

foreach (config('tenancy.central_domains') as $dominio) {
    Route::domain($dominio)->group(function () {
        Route::view('/', 'central.inicio');
    });
}
