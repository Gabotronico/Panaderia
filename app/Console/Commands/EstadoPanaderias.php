<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venta;
use App\Support\Licencias\Licencia;
use App\Support\Tenancy\Contexto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Estado de cada panadería de un vistazo: si su base responde, cuántos datos
 * tiene y cómo está su licencia. Sirve para detectar la que quedó caída sin
 * tener que entrar una por una.
 */
class EstadoPanaderias extends Command
{
    protected $signature = 'obrador:panaderias:estado {--tenant= : Solo esta panadería}';

    protected $description = 'Muestra el estado de la base y la licencia de cada panadería';

    public function handle(): int
    {
        $consulta = Tenant::query()->orderBy('id');

        if (($id = (string) $this->option('tenant')) !== '') {
            $consulta->whereKey($id);
        }

        $panaderias = $consulta->get();

        if ($panaderias->isEmpty()) {
            $this->warn('No hay panaderías registradas.');

            return self::SUCCESS;
        }

        $filas = [];
        $caidas = 0;

        foreach ($panaderias as $tenant) {
            try {
                $datos = Contexto::ejecutar($tenant, fn (): array => [
                    'base' => DB::connection()->getDatabaseName(),
                    'usuarios' => User::query()->count(),
                    'productos' => Producto::query()->count(),
                    'ventas' => Venta::query()->count(),
                    'migraciones' => DB::table('migrations')->count(),
                ]);

                $filas[] = [
                    $tenant->id,
                    $datos['base'],
                    'OK',
                    Licencia::estado($tenant->id),
                    $datos['usuarios'],
                    $datos['productos'],
                    $datos['ventas'],
                    $datos['migraciones'],
                ];
            } catch (\Throwable $exception) {
                $caidas++;
                $filas[] = [
                    $tenant->id,
                    '—',
                    'ERROR',
                    Licencia::estado($tenant->id),
                    '—',
                    '—',
                    '—',
                    '—',
                ];
            }
        }

        $this->table(
            ['Panadería', 'Base', 'Estado', 'Licencia', 'Usuarios', 'Productos', 'Ventas', 'Migr.'],
            $filas,
        );

        if ($caidas > 0) {
            $this->warn("{$caidas} panadería(s) no responden. El detalle está en el log (docker compose logs app).");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
