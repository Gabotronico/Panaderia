<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Respaldos\Respaldo;
use Illuminate\Console\Command;

/**
 * Carga un respaldo .zip (de la aplicación de escritorio o de la web) en una
 * panadería. Pensado para el traspaso: se da de alta la panadería con
 * --sin-admin y se le importa el respaldo que entregó el cliente, que trae
 * sus propios usuarios.
 */
class ImportarRespaldoTenant extends Command
{
    protected $signature = 'obrador:tenant-importar
        {id : Id de la panadería}
        {archivo : Ruta al respaldo .zip}
        {--force : No pedir confirmación}';

    protected $description = 'Reemplaza los datos de una panadería con los de un respaldo .zip';

    public function handle(): int
    {
        $tenant = Tenant::query()->find($this->argument('id'));
        $archivo = (string) $this->argument('archivo');

        if (! $tenant) {
            $this->error("No existe la panadería \"{$this->argument('id')}\".");

            return self::FAILURE;
        }

        if (! is_file($archivo)) {
            $this->error("No se encontró el archivo {$archivo}.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Se reemplazarán todos los datos de \"{$tenant->getTenantKey()}\". ¿Continuar?")) {
            return self::FAILURE;
        }

        try {
            $copiadas = $tenant->run(fn () => Respaldo::restaurar(realpath($archivo)));
        } catch (\Throwable $exception) {
            $this->error('No se pudo importar: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Tabla', 'Filas'],
            collect($copiadas)->filter()->map(fn ($filas, $tabla) => [$tabla, $filas])->values()->all()
        );
        $this->info('Respaldo importado. La copia de los datos anteriores quedó en '
            .$tenant->run(fn () => storage_path('app/backups')));

        return self::SUCCESS;
    }
}
