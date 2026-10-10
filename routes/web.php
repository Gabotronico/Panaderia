<?php

use App\Http\Controllers\Central\PanaderiaController;
use App\Http\Controllers\Central\PermisoCertificadoController;
use App\Http\Controllers\Central\SesionController;
use App\Http\Middleware\SoloDominioCentral;
use Illuminate\Support\Facades\Route;

/*
| Rutas del dominio central (obrador.com).
|
| La portada se ata a cada dominio central para no ganarle a la "/" de las
| panaderías (routes/tenant.php). El panel vive bajo /panel, un path que las
| panaderías no usan, y se registra una sola vez para que route() arme las
| URLs con el dominio de la petición; SoloDominioCentral lo cierra en los
| subdominios.
*/

foreach (config('tenancy.central_domains') as $dominio) {
    Route::domain($dominio)->group(function () {
        Route::view('/', 'central.inicio');
    });
}

// Consulta interna de Caddy antes de emitir un certificado HTTPS (ver
// docker/caddy/Caddyfile). Sin dominio: Caddy la llama como app:8080.
Route::get('interno/tls-permitido', PermisoCertificadoController::class)->name('tls.permitido');

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
