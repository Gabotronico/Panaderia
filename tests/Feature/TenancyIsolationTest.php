<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Tenant;
use App\Models\User;
use App\Support\InstalacionInicial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aislamiento real entre panaderías: cada una con su propia base (archivos
 * SQLite en database/), creada y migrada por los mismos eventos que en
 * producción. El resto de la suite corre con la base compartida.
 */
class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $conPanaderiaDePrueba = false;

    /** @var list<Tenant> */
    private array $creadas = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenancy.database.prefix' => 'testing_tenant_',
            'tenancy.database.suffix' => '.sqlite',
        ]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();

        foreach ($this->creadas as $tenant) {
            $tenant->delete();
        }

        parent::tearDown();
    }

    public function test_each_bakery_only_sees_its_own_data(): void
    {
        $luna = $this->crearPanaderia('luna', 'Panadería Luna', 'ana@luna.test');
        $sol = $this->crearPanaderia('sol', 'Panadería Sol', 'beto@sol.test');

        $luna->run(fn () => Categoria::query()->create(['nombre' => 'Solo de Luna']));

        $luna->run(function () {
            $this->assertSame(['ana@luna.test'], User::query()->pluck('email')->all());
            $this->assertTrue(Categoria::query()->where('nombre', 'Solo de Luna')->exists());
        });

        $sol->run(function () {
            $this->assertSame(['beto@sol.test'], User::query()->pluck('email')->all());
            $this->assertFalse(Categoria::query()->where('nombre', 'Solo de Luna')->exists());
        });
    }

    public function test_each_subdomain_serves_its_own_bakery(): void
    {
        $this->crearPanaderia('luna', 'Panadería Luna', 'ana@luna.test');
        $this->crearPanaderia('sol', 'Panadería Sol', 'beto@sol.test');

        $this->get('http://luna.localhost/login')->assertOk()->assertSee('Panadería Luna');
        $this->get('http://sol.localhost/login')->assertOk()->assertSee('Panadería Sol');

        // Las credenciales de una panadería no existen en la otra.
        $this->from('http://sol.localhost/login')
            ->post('http://sol.localhost/login', [
                'email' => 'ana@luna.test',
                'password' => 'ClaveSegura2026',
            ])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('http://luna.localhost/login', [
            'email' => 'ana@luna.test',
            'password' => 'ClaveSegura2026',
        ])->assertRedirect('http://luna.localhost/home');
        $this->assertAuthenticated();
    }

    public function test_unknown_subdomains_and_the_central_domain_do_not_serve_bakeries(): void
    {
        $this->get('http://nadie.localhost/login')->assertNotFound();
        $this->get('http://localhost/login')->assertNotFound();
        $this->get('http://localhost/')->assertOk();
    }

    /**
     * El panel central trabaja dentro de una panadería en medio de su propia
     * petición. StartSession guarda al final con el driver vigente del
     * gestor: si entrar o salir de la panadería lo descartaba, se guardaba
     * una sesión vacía y el siguiente formulario del panel fallaba con 419.
     */
    public function test_working_inside_a_bakery_keeps_the_running_session(): void
    {
        $luna = $this->crearPanaderia('luna', 'Panadería Luna', 'ana@luna.test');

        $sesion = app('session')->driver();
        $sesion->start();
        $sesion->put('dato', 'del panel');

        $luna->run(fn () => User::query()->count());

        $this->assertSame($sesion, app('session')->driver());
        $this->assertSame('del panel', app('session')->driver()->get('dato'));
    }

    /** En producción el dominio central es un subdominio: obrador.incgroupbo.com. */
    public function test_bakeries_work_under_a_multi_level_central_domain(): void
    {
        config(['tenancy.central_domains' => ['obrador.incgroupbo.com']]);
        $this->crearPanaderia('luna', 'Panadería Luna', 'ana@luna.test');

        $this->get('http://luna.obrador.incgroupbo.com/login')->assertOk()->assertSee('Panadería Luna');
        $this->get('http://otra.obrador.incgroupbo.com/login')->assertNotFound();
        $this->get('http://obrador.incgroupbo.com/login')->assertNotFound();
    }

    private function crearPanaderia(string $id, string $negocio, string $email): Tenant
    {
        $tenant = Tenant::query()->create(['id' => $id]);
        $tenant->domains()->create(['domain' => $id]);
        $this->creadas[] = $tenant;

        $tenant->run(fn () => InstalacionInicial::ejecutar($negocio, 'Admin', $email, 'ClaveSegura2026'));

        return $tenant;
    }
}
