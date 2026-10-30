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

    public function test_each_path_serves_its_own_bakery(): void
    {
        $this->crearPanaderia('luna', 'Panadería Luna', 'ana@luna.test');
        $this->crearPanaderia('sol', 'Panadería Sol', 'beto@sol.test');

        $this->get('http://localhost/luna/login')->assertOk()->assertSee('Panadería Luna');
        $this->get('http://localhost/sol/login')->assertOk()->assertSee('Panadería Sol');

        // Las credenciales de una panadería no existen en la otra.
        $this->from('http://localhost/sol/login')
            ->post('http://localhost/sol/login', [
                'email' => 'ana@luna.test',
                'password' => 'ClaveSegura2026',
            ])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Y las URLs que arma la aplicación quedan dentro de la panadería.
        $this->post('http://localhost/luna/login', [
            'email' => 'ana@luna.test',
            'password' => 'ClaveSegura2026',
        ])->assertRedirect('http://localhost/luna/home');
        $this->assertAuthenticated();

        $this->get('http://localhost/luna/')->assertRedirect('http://localhost/luna/login');
    }

    public function test_unknown_bakeries_and_system_paths_do_not_serve_bakeries(): void
    {
        $this->get('http://localhost/nadie/login')->assertNotFound();
        $this->get('http://localhost/login')->assertNotFound();
        $this->get('http://localhost/')->assertOk();
        $this->get('http://localhost/panel/login')->assertOk();
    }

    /**
     * Todas las panaderías comparten dominio: cada una necesita su propia
     * cookie de sesión, limitada a su ruta, para que entrar a una no cierre
     * la sesión de otra en el mismo navegador.
     */
    public function test_each_bakery_has_its_own_session_cookie(): void
    {
        $this->crearPanaderia('luna', 'Panadería Luna', 'ana@luna.test');
        $this->crearPanaderia('sol', 'Panadería Sol', 'beto@sol.test');

        $cookie = config('session.cookie');

        $luna = $this->get('http://localhost/luna/login')->getCookie($cookie.'-luna', false);
        $sol = $this->get('http://localhost/sol/login')->getCookie($cookie.'-sol', false);

        $this->assertSame('/luna', $luna?->getPath());
        $this->assertSame('/sol', $sol?->getPath());
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

    private function crearPanaderia(string $id, string $negocio, string $email): Tenant
    {
        $tenant = Tenant::query()->create(['id' => $id]);
        $tenant->domains()->create(['domain' => $id]);
        $this->creadas[] = $tenant;

        $tenant->run(fn () => InstalacionInicial::ejecutar($negocio, 'Admin', $email, 'ClaveSegura2026'));

        return $tenant;
    }
}
