<?php

declare(strict_types=1);

/*
 * Datos de prueba para el entorno local (compose.local.yaml).
 *
 * Deja la panadería indicada con un almacén, un cajero, productos con stock e
 * insumos, para que las pantallas no salgan vacías. Se puede correr las veces
 * que haga falta: actualiza lo que ya existe en vez de duplicarlo.
 *
 *   docker compose -f compose.local.yaml exec -T app php docker/local/sembrar-demo.php luna
 *
 * El administrador lo crea obrador:tenant-crear (docker/local/preparar.sh);
 * acá solo se agrega el cajero y la mercadería de ejemplo.
 */

use App\Models\Almacen;
use App\Models\AppSetting;
use App\Models\Categoria;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Console\Kernel;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$id = $argv[1] ?? 'luna';
$tenant = Tenant::query()->find($id);

if ($tenant === null) {
    fwrite(STDERR, "No existe la panadería \"{$id}\".".
        " Creala antes con: php artisan obrador:tenant-crear {$id}\n");
    exit(1);
}

// El nombre visible se toma del propio tenant y solo se corrige si falta o si
// quedó mal codificado (pasa cuando el alta se hace por consola de Windows con
// un acento: "Panadería" termina guardado como "PanaderÃ­a"). Se arma desde
// este archivo, que está en UTF-8, y no con acentos venidos de la consola.
$negocio = trim((string) ($tenant->negocio ?? ''));

if ($negocio === '' || str_contains($negocio, 'Ã')) {
    $negocio = 'Panadería '.ucfirst($id);
    $tenant->update(['negocio' => $negocio]);
}

$tenant->run(function () use ($negocio, $id): void {
    $actual = AppSetting::read('business_name');

    if ($actual === null || trim($actual) === '' || str_contains($actual, 'Ã')) {
        AppSetting::write('business_name', $negocio);
    }

    // Una panadería creada con --sin-admin todavía no tiene roles.
    if (! Role::query()->where('name', 'Cajero')->exists()) {
        app(RoleSeeder::class)->run();
    }

    $almacen = Almacen::query()->firstOrCreate(
        ['nombre' => 'Mostrador'],
        ['descripcion' => 'Venta al público', 'activo' => true],
    );

    $cajero = User::query()->firstOrNew(['email' => "cajero@{$id}.local"]);
    $cajero->fill([
        'name' => 'Cajero de Prueba',
        'password' => 'Cajero12345',
        'almacen_id' => $almacen->id,
    ])->save();
    $cajero->assignRole('Cajero');

    $productos = [
        ['Panes', 'Pan francés', 'Unidad', 0.50, 120],
        ['Panes', 'Marraqueta', 'Unidad', 0.80, 90],
        ['Panes', 'Cuñapé', 'Unidad', 3.00, 40],
        ['Pasteles', 'Empanada de queso', 'Unidad', 5.00, 25],
        ['Pasteles', 'Torta de chocolate', 'Porción', 12.00, 8],
        ['Bebidas', 'Café con leche', 'Vaso', 7.00, 60],
    ];

    foreach ($productos as [$categoria, $nombre, $medida, $precio, $stock]) {
        $producto = Producto::query()->updateOrCreate(
            ['nombre' => $nombre],
            [
                'categoria_id' => Categoria::query()->where('nombre', $categoria)->value('id'),
                'descripcion' => "{$nombre} por {$medida}",
                'precio_venta' => $precio,
                'stock' => $stock,
                'stock_minimo' => 10,
                'activo' => true,
            ],
        );

        $producto->almacenes()->syncWithoutDetaching([$almacen->id => ['stock' => $stock]]);
    }

    $insumos = [
        ['Harina de trigo', 'kg', 50, 10, 7.50],
        ['Azúcar', 'kg', 25, 5, 6.00],
        ['Queso', 'kg', 6, 3, 38.00],
        ['Levadura', 'kg', 2, 1, 45.00],
    ];

    foreach ($insumos as [$nombre, $unidad, $cantidad, $minimo, $costo]) {
        Insumo::query()->updateOrCreate(
            ['nombre' => $nombre],
            [
                'unidad_medida' => $unidad,
                'cantidad_stock' => $cantidad,
                'stock_minimo' => $minimo,
                'costo_unitario' => $costo,
                'activo' => true,
            ],
        );
    }
});

echo "Datos de prueba listos en \"{$id}\": cajero@{$id}.local / Cajero12345\n";
