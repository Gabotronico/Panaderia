<?php

namespace App\Console\Commands;

use App\Models\Persona;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Identidad\Cuentas;
use Illuminate\Console\Command;

/**
 * Crea la cuenta global de los usuarios que ya existen en cada panadería.
 *
 * Es idempotente: se puede correr las veces que haga falta. La contraseña de la
 * persona queda con el hash del primer usuario que tenga ese correo; si el
 * mismo correo aparece en varias panaderías, se listan al final para revisar
 * cuál debe quedar (no se puede comparar si las contraseñas eran iguales).
 */
class SincronizarPersonas extends Command
{
    protected $signature = 'obrador:personas-sincronizar {--dry : Solo muestra lo que haría}';

    protected $description = 'Vincula los usuarios existentes de cada panadería con su cuenta global';

    public function handle(): int
    {
        $seco = (bool) $this->option('dry');
        $tenants = Tenant::query()->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No hay panaderías registradas.');

            return self::SUCCESS;
        }

        $antes = Persona::query()->count();
        $usuarios = 0;
        $porCorreo = [];

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $seco, &$usuarios, &$porCorreo): void {
                foreach (User::query()->orderBy('id')->get() as $user) {
                    $usuarios++;
                    $porCorreo[mb_strtolower((string) $user->email)][] = $tenant->id;

                    if ($seco) {
                        $this->line("  · {$tenant->id}: {$user->email}");

                        continue;
                    }

                    Cuentas::vincular($tenant, $user);
                }
            });
        }

        if ($seco) {
            $this->info("[dry] Se revisarían {$usuarios} usuarios de {$tenants->count()} panaderías.");

            return self::SUCCESS;
        }

        $creadas = Persona::query()->count() - $antes;

        $this->info("Panaderías: {$tenants->count()} · usuarios: {$usuarios} · cuentas nuevas: {$creadas}");

        $repetidos = array_filter($porCorreo, fn (array $lista) => count($lista) > 1);

        if ($repetidos !== []) {
            $this->newLine();
            $this->warn('Correos que aparecen en más de una panadería (quedó la contraseña más antigua):');

            foreach ($repetidos as $correo => $lista) {
                $this->line('  · '.$correo.' → '.implode(', ', $lista));
            }
        }

        return self::SUCCESS;
    }
}
