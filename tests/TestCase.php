<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;

abstract class TestCase extends BaseTestCase
{
    /**
     * Si el test arranca dentro de la panadería "prueba". Los tests de
     * aislamiento entre panaderías lo apagan y arman las suyas.
     */
    protected bool $conPanaderiaDePrueba = true;

    protected function setUp(): void
    {
        parent::setUp();

        // La licencia se memoriza por proceso y cada test parte de cero.
        \App\Support\Licencias\Licencia::olvidar();

        // Solo cuando RefreshDatabase ya dejó la base migrada: los tests
        // unitarios no tocan la base y BackupTest la migra por su cuenta.
        if ($this->conPanaderiaDePrueba && Schema::hasTable('domains')) {
            $this->iniciarPanaderiaDePrueba();
        }
    }

    protected function tearDown(): void
    {
        if ($this->app && tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    /**
     * En los tests las tablas centrales y las de la panadería conviven en la
     * misma base, así RefreshDatabase las migra juntas.
     */
    protected function setUpTraits()
    {
        $this->app['migrator']->path(database_path('migrations/tenant'));

        return parent::setUpTraits();
    }

    /**
     * Da de alta la panadería "prueba" sobre la base del test y la deja
     * iniciada, con las URLs bajo su ruta (/prueba). Se quita el
     * bootstrapper de base de datos para que la panadería use la misma base
     * en memoria que el test: el aislamiento real entre bases se prueba
     * aparte (TenancyIsolationTest).
     */
    protected function iniciarPanaderiaDePrueba(): Tenant
    {
        config(['tenancy.bootstrappers' => array_values(array_diff(
            config('tenancy.bootstrappers'),
            [DatabaseTenancyBootstrapper::class]
        ))]);

        // Sin eventos: TenantCreated crearía y migraría una base aparte.
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create(['id' => 'prueba']));
        $tenant->domains()->create(['domain' => 'prueba']);

        // Al iniciarla, route() arma las URLs bajo /prueba.
        URL::forceRootUrl('http://localhost');
        tenancy()->initialize($tenant);

        return $tenant;
    }
}
