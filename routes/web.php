<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CorteCajaController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\FinanzaController;
use App\Http\Controllers\GastoFijoController;
use App\Http\Controllers\GastoPagoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\LocalStorageController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/setup', [SetupController::class, 'create'])->name('setup.create');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

// Ruta pública
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas que requieren autenticación
Route::middleware('auth')->group(function () {
    Route::get('storage/{path}', LocalStorageController::class)
        ->where('path', '.*')
        ->name('local-storage.show');

    // Dashboard/Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    // Rutas de Categorías
    Route::resource('categorias', CategoriaController::class);

    // Rutas de Productos
    Route::post('productos/{producto}/transferir-almacen', [ProductoController::class, 'transferirAlmacen'])->name('productos.transferirAlmacen');
    Route::resource('productos', ProductoController::class);

    // Rutas de Insumos
    Route::post('insumos/{insumo}/comprar', [InsumoController::class, 'comprar'])->name('insumos.comprar');
    Route::post('insumos/{insumo}/merma', [InsumoController::class, 'merma'])->name('insumos.merma');
    Route::post('insumos/{insumo}/mover-a-producto', [InsumoController::class, 'moverAProducto'])->name('insumos.moverAProducto');
    Route::resource('insumos', InsumoController::class);

    // Rutas de Producción. Una corrida no se edita: ya movió stock, así que
    // se anula y se vuelve a cargar.
    Route::resource('produccion', ProduccionController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy'])
        ->parameters(['produccion' => 'produccion']);

    // Rutas de Almacenes
    Route::post('almacenes/{almacen}/asignar-cajero', [AlmacenController::class, 'asignarCajero'])->name('almacenes.asignarCajero');
    Route::delete('almacenes/{almacen}/cajero/{user}', [AlmacenController::class, 'desasignarCajero'])->name('almacenes.desasignarCajero');
    Route::post('almacenes/{almacen}/stock', [AlmacenController::class, 'distribuirStock'])->name('almacenes.distribuirStock');
    Route::post('almacenes/{almacen}/producto/{producto}/retornar', [AlmacenController::class, 'retornarBodega'])->name('almacenes.retornarBodega');
    Route::resource('almacenes', AlmacenController::class)->parameters(['almacenes' => 'almacen']);

    // Rutas de Ventas
    Route::resource('ventas', VentaController::class);

    // Rutas de Cortes de Caja
    // La corrección de un corte ya cerrado va aparte del cierre normal:
    // distinto permiso, distinto formulario y deja rastro en observaciones.
    Route::get('cortes/{corte}/cierre/editar', [CorteCajaController::class, 'editarCierre'])->name('cortes.cierre.editar');
    Route::put('cortes/{corte}/cierre', [CorteCajaController::class, 'actualizarCierre'])->name('cortes.cierre.actualizar');
    Route::resource('cortes', CorteCajaController::class);

    // Rutas de RR.HH.
    Route::resource('cargos', CargoController::class)->except(['show']);
    Route::post('empleados/{empleado}/adelanto', [EmpleadoController::class, 'adelanto'])->name('empleados.adelanto');
    Route::resource('empleados', EmpleadoController::class);
    Route::get('asistencias/registrar', [AsistenciaController::class, 'registrar'])->name('asistencias.registrar');
    Route::resource('asistencias', AsistenciaController::class)->only(['index', 'store', 'edit', 'update']);
    Route::post('planillas/{planilla}/cerrar', [PlanillaController::class, 'cerrar'])->name('planillas.cerrar');
    Route::post('planillas/{planilla}/pagar', [PlanillaController::class, 'pagar'])->name('planillas.pagar');
    Route::get('planillas/{planilla}/pdf', [PlanillaController::class, 'descargarPdf'])->name('planillas.pdf');
    Route::resource('planillas', PlanillaController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // Gestión de usuarios del sistema
    Route::resource('usuarios', UserController::class)
         ->parameters(['usuarios' => 'usuario'])
         ->except(['show']);

    // Resumen financiero consolidado
    Route::get('finanzas', [FinanzaController::class, 'index'])->name('finanzas.index');

    // Rutas de Gastos Fijos
    Route::resource('gastos-fijos', GastoFijoController::class)->except(['show']);
    Route::get('gastos-pagos', [GastoPagoController::class, 'index'])->name('gastos-pagos.index');
    Route::get('gastos-pagos/anual', [GastoPagoController::class, 'anual'])->name('gastos-pagos.anual');
    Route::post('gastos-pagos/generar', [GastoPagoController::class, 'generar'])->name('gastos-pagos.generar');
    Route::patch('gastos-pagos/{gastoPago}/pagar', [GastoPagoController::class, 'pagar'])->name('gastos-pagos.pagar');
    Route::patch('gastos-pagos/{gastoPago}/anular', [GastoPagoController::class, 'anularPago'])->name('gastos-pagos.anular');
    Route::patch('gastos-pagos/{gastoPago}/ajustar', [GastoPagoController::class, 'ajustar'])->name('gastos-pagos.ajustar');
    Route::delete('gastos-pagos/{gastoPago}', [GastoPagoController::class, 'destroy'])->name('gastos-pagos.destroy');
    Route::delete('gastos-pagos-mes', [GastoPagoController::class, 'borrarMes'])->name('gastos-pagos.borrar-mes');

    // Rutas de Reportes
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/ventas', [ReporteController::class, 'ventas'])->name('ventas');
        Route::get('/productos-mas-vendidos', [ReporteController::class, 'productosMasVendidos'])->name('productos-mas-vendidos');
        Route::get('/inventario', [ReporteController::class, 'inventario'])->name('inventario');
        Route::get('/cortes-caja', [ReporteController::class, 'cortesCaja'])->name('cortes-caja');
    });

    Route::prefix('sistema')->name('system.')->group(function () {
        Route::get('/respaldos', [BackupController::class, 'index'])->name('backups.index');
        Route::get('/respaldos/descargar', [BackupController::class, 'download'])->name('backups.download');
        Route::post('/respaldos/restaurar', [BackupController::class, 'restore'])->name('backups.restore');
    });
});

// Rutas de autenticación
require __DIR__.'/auth.php';
