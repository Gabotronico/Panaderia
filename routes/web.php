<?php

use App\Http\Controllers\Central\PanaderiaController;
use App\Http\Controllers\Central\SesionController;
use App\Http\Controllers\EscritorioController;
use App\Http\Controllers\Interno\LicenciaController;
use App\Http\Middleware\FirmaInterna;
use App\Http\Middleware\SoloDominioCentral;
use Illuminate\Support\Facades\Route;

/*
| Rutas del dominio central (obrador.incgroupbo.com).
|
| Las panaderías viven bajo /{id} (routes/tenant.php, registradas después),
| así que estas les ganan: "panel" y "up" son ids reservados. La portada se
| ata a cada dominio central; el panel se registra una sola vez para que
| route() arme las URLs con el dominio de la petición, y SoloDominioCentral
| lo cierra en cualquier otro host.
*/

/*
| Endpoint interno para el panel central de INC Group.
|
| No lo usa ningún navegador: la firma HMAC (middleware FirmaInterna) es la
| que autoriza. "interno" es un id reservado, así que nunca choca con una
| panadería.
*/

Route::middleware(FirmaInterna::class)->prefix('interno')->name('interno.')->group(function () {
    Route::get('licencias', [LicenciaController::class, 'mostrar'])->name('licencias');
    Route::post('licencia', [LicenciaController::class, 'sincronizar'])->name('licencia');
});

/*
| App de escritorio.
|
| La ventana ya no trabaja contra una base local: la primera vez pide el
| código de la panadería y de ahí en adelante abre el sistema del VPS. La raíz
| se registra solo dentro de la app empaquetada; en el navegador sigue
| respondiendo la portada del dominio central.
*/

Route::get('/interno/escritorio', [EscritorioController::class, 'mostrar'])->name('escritorio.mostrar');
Route::post('/interno/escritorio', [EscritorioController::class, 'guardar'])->name('escritorio.guardar');
Route::post('/interno/escritorio/olvidar', [EscritorioController::class, 'olvidar'])->name('escritorio.olvidar');

if (config('nativephp-internal.running')) {
    Route::get('/', [EscritorioController::class, 'raiz'])->name('escritorio.raiz');
}

foreach (config('tenancy.central_domains') as $dominio) {
    Route::domain($dominio)->group(function () {
        Route::view('/', 'central.inicio');
    });
}

Route::middleware(SoloDominioCentral::class)->prefix('panel')->name('central.')->group(function () {
    Route::middleware('guest:superadmin')->group(function () {
        Route::get('login', [SesionController::class, 'create'])->name('login');
        Route::post('login', [SesionController::class, 'store'])->middleware('throttle:login');
    });

    Route::middleware('auth:superadmin')->group(function () {
        Route::post('logout', [SesionController::class, 'destroy'])->name('logout');

        Route::get('/', fn () => redirect()->route('central.panaderias.index'));
        Route::resource('panaderias', PanaderiaController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy'])
            ->parameters(['panaderias' => 'panaderia']);
        Route::post('panaderias/{panaderia}/administrador', [PanaderiaController::class, 'administrador'])
            ->name('panaderias.administrador');
        Route::post('panaderias/{panaderia}/importar', [PanaderiaController::class, 'importar'])
            ->name('panaderias.importar');
    });
});
