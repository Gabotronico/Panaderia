<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Tenancy\Contexto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Migra las panaderías una por una.
 *
 * Reemplaza a `tenants:migrate` en el arranque del contenedor: si una
 * panadería tiene la base caída, se registra el error y se sigue con las
 * demás. Con el comando de stancl, un solo error cortaba el arranque y dejaba
 * sin servicio a todas.
 *
 * Sale con código 0 aunque alguna falle (para que el contenedor levante);
 * con --strict devuelve error, útil para un chequeo manual.
 */
class MigrarPanaderias extends Command
{
    protected $signature = 'obrador:panaderias:migrar
        {--tenant= : Migrar solo esta panadería}
        {--strict : Salir con error si alguna panadería falla}';

    protected $description = 'Aplica las migraciones de cada panadería sin que una caída frene a las demás';

    public function handle(): int
    {
        $consulta = Tenant::query()->orderBy('id');

        if (($id = (string) $this->option('tenant')) !== '') {
            $consulta->whereKey($id);
        }

        $panaderias = $consulta->get();

        if ($panaderias->isEmpty()) {
            $this->warn('No hay panaderías para migrar.');

            return self::SUCCESS;
        }

        /** @var array<string, string> $fallidas */
        $fallidas = [];

        foreach ($panaderias as $tenant) {
            try {
                $codigo = Contexto::ejecutar($tenant, fn () => Artisan::call('tenants:migrate', ['--tenants' => $tenant->id]));

                if ($codigo !== 0) {
                    throw new \RuntimeException('El comando de migración devolvió '.$codigo.'.');
                }

                $this->line("  ✓ {$tenant->id}");
            } catch (\Throwable $exception) {
                $fallidas[$tenant->id] = $exception->getMessage();
                $this->error("  ✗ {$tenant->id}: {$exception->getMessage()}");

                Log::error('No se pudieron migrar las tablas de una panadería.', [
                    'tenant' => $tenant->id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($fallidas !== []) {
            $this->newLine();
            $this->warn(sprintf(
                '%d de %d panaderías con problemas: %s. El resto sigue funcionando.',
                count($fallidas),
                $panaderias->count(),
                implode(', ', array_keys($fallidas)),
            ));

            return $this->option('strict') ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Migraciones al día en '.$panaderias->count().' panadería(s).');

        return self::SUCCESS;
    }
}
