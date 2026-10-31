<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

/**
 * Ejecuta código dentro de una panadería y siempre deja el contexto como estaba.
 *
 * `$tenant->run()` de stancl no usa try/finally: si la base está caída y la
 * inicialización (o el callback) falla, la tenancy queda a medio iniciar. A
 * partir de ahí, el siguiente acceso —o el siguiente tenant de un bucle— también
 * falla, y un error en una panadería se convierte en un error en todas.
 *
 * Este envoltorio cierra el contexto pase lo que pase:
 *
 * - si la llamada venía de una panadería (por ejemplo, restaurar un respaldo
 *   desde adentro del sistema), vuelve a esa panadería;
 * - si venía del central (login, comandos, panel), termina la tenancy.
 */
final class Contexto
{
    public static function ejecutar(Tenant $tenant, callable $callback): mixed
    {
        $anterior = function_exists('tenant') ? tenant() : null;

        try {
            return $tenant->run($callback);
        } finally {
            try {
                if ($anterior !== null) {
                    if (! tenancy()->initialized
                        || (string) tenant('id') !== (string) $anterior->getTenantKey()) {
                        tenancy()->initialize($anterior);
                    }
                } elseif (tenancy()->initialized) {
                    tenancy()->end();
                }
            } catch (\Throwable) {
                // El contexto ya quedó roto: no hay nada más que hacer.
            }
        }
    }
}
