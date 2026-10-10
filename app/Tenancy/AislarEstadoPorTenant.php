<?php

namespace App\Tenancy;

use App\Models\AppSetting;
use Illuminate\Foundation\Application;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * Corta el estado que el arranque de Laravel deja armado antes de saber qué
 * panadería atiende la petición:
 *
 * - La caché y la sesión usan el driver "database" de la conexión por
 *   defecto. Si ya se resolvieron, siguen apuntando a la base central; se
 *   descartan para que la próxima lectura las arme sobre la base de la
 *   panadería. Pasa, por ejemplo, con las rutas Route::view: el router
 *   instancia ViewController (y con él la fábrica de vistas, que resuelve la
 *   sesión) antes de correr los middlewares, y StartSession terminaba
 *   leyendo la sesión de la base central: el usuario aparecía deslogueado.
 * - spatie/laravel-permission guarda roles y permisos con una sola clave y
 *   los memoriza en el registrar. Los ids de rol no coinciden entre bases,
 *   así que compartir esa caché le daría a un usuario permisos de otra
 *   panadería.
 * - El nombre del negocio se memoriza por proceso en AppSetting.
 * - El guard por defecto. En el panel central es "superadmin" (lo fija el
 *   middleware auth:superadmin), y spatie crea roles y permisos con el guard
 *   por defecto: dados de alta desde el panel, los roles de la panadería
 *   quedaban con un guard que sus usuarios no tienen.
 */
class AislarEstadoPorTenant implements TenancyBootstrapper
{
    private ?string $nombreOriginal = null;

    private ?string $guardOriginal = null;

    public function __construct(private Application $app)
    {
    }

    public function bootstrap(Tenant $tenant): void
    {
        $this->nombreOriginal ??= config('app.name');
        $this->guardOriginal ??= config('auth.defaults.guard');
        config(['auth.defaults.guard' => 'web']);

        $this->reiniciar('spatie.permission.cache.tenant.'.$tenant->getTenantKey());

        try {
            if (($nombre = AppSetting::nombreEnCache()) !== null) {
                config(['app.name' => $nombre]);
            }
        } catch (\Throwable) {
            // La base de la panadería puede estar recién creada y sin migrar.
        }
    }

    public function revert(): void
    {
        $this->reiniciar(config('permission.cache.key'));

        if ($this->nombreOriginal !== null) {
            config(['app.name' => $this->nombreOriginal]);
        }

        if ($this->guardOriginal !== null) {
            config(['auth.defaults.guard' => $this->guardOriginal]);
        }
    }

    private function reiniciar(string $clavePermisos): void
    {
        $this->app['cache']->forgetDriver();
        $this->app->forgetInstance('cache.store');

        // Solo una sesión que todavía no arrancó: si ya arrancó, es la de la
        // petición en curso (el panel central trabajando sobre una panadería)
        // y StartSession la guarda al final con el driver vigente del gestor.
        // Descartarla hacía que se guardara una sesión vacía en su lugar, y el
        // siguiente formulario del panel fallaba con 419.
        if (! $this->haySesionIniciada()) {
            $this->app['session']->forgetDrivers();
            $this->app->forgetInstance('session.store');
        }

        AppSetting::olvidarNombre();

        $registrar = $this->app->make(PermissionRegistrar::class);
        $registrar->initializeCache();
        $registrar->cacheKey = $clavePermisos;
        $registrar->clearPermissionsCollection();
    }

    private function haySesionIniciada(): bool
    {
        foreach ($this->app['session']->getDrivers() as $sesion) {
            if ($sesion->isStarted()) {
                return true;
            }
        }

        return false;
    }
}
