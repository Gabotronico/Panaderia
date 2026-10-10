<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AltaPanaderia;
use App\Support\Respaldos\Respaldo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Panel del superadministrador en el dominio central. Las panaderías que
 * crea tienen bases reales (archivos SQLite), como en producción.
 */
class CentralPanelTest extends TestCase
{
    use RefreshDatabase;

    protected bool $conPanaderiaDePrueba = false;

    private SuperAdmin $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenancy.database.prefix' => 'testing_panel_',
            'tenancy.database.suffix' => '.sqlite',
        ]);

        $this->superAdmin = SuperAdmin::query()->create([
            'name' => 'Soporte',
            'email' => 'soporte@obrador.test',
            'password' => 'ClaveSoporte2026x',
        ]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();

        foreach (Tenant::all() as $tenant) {
            AltaPanaderia::eliminar($tenant);
        }

        parent::tearDown();
    }

    public function test_the_panel_requires_a_super_admin_and_only_exists_on_the_central_domain(): void
    {
        $this->get('http://localhost/panel/panaderias')->assertRedirect('http://localhost/panel/login');
        $this->get('http://localhost/panel/login')->assertOk();

        $this->post('http://localhost/panel/login', [
            'email' => 'soporte@obrador.test',
            'password' => 'ClaveSoporte2026x',
        ])->assertRedirect('http://localhost/panel/panaderias');
        $this->assertAuthenticatedAs($this->superAdmin, 'superadmin');

        $this->get('http://localhost/panel/panaderias')->assertOk();
        $this->get('http://luna.localhost/panel/login')->assertNotFound();
    }

    public function test_a_super_admin_creates_a_bakery_with_its_administrator(): void
    {
        $this->actingAs($this->superAdmin, 'superadmin')
            ->post('http://localhost/panel/panaderias', [
                'negocio' => 'Panadería Luna',
                'id' => 'luna',
                'dominios_texto' => 'panaderialuna.com',
                'modo' => 'administrador',
                'nombre' => 'Ana',
                'email' => 'ana@luna.test',
                'password' => 'ClaveSegura2026',
                'password_confirmation' => 'ClaveSegura2026',
            ])
            ->assertRedirect('http://localhost/panel/panaderias/luna');

        $tenant = Tenant::query()->findOrFail('luna');
        $this->assertSame('Panadería Luna', $tenant->negocio);
        $this->assertEqualsCanonicalizing(['luna', 'panaderialuna.com'], $tenant->domains->pluck('domain')->all());

        $tenant->run(function () {
            $ana = User::query()->where('email', 'ana@luna.test')->firstOrFail();
            $this->assertTrue($ana->hasRole('Administrador'));
        });

        $this->get('http://localhost/panel/panaderias/luna')->assertOk()->assertSee('ana@luna.test');
    }

    public function test_reserved_or_taken_ids_are_rejected(): void
    {
        $this->actingAs($this->superAdmin, 'superadmin');

        foreach (['panel', 'Con Espacios', '-luna'] as $id) {
            $this->post('http://localhost/panel/panaderias', [
                'negocio' => 'X', 'id' => $id, 'modo' => 'administrador',
                'nombre' => 'Ana', 'email' => 'ana@x.test',
                'password' => 'ClaveSegura2026', 'password_confirmation' => 'ClaveSegura2026',
            ])->assertSessionHasErrors('id');
        }

        $this->assertSame(0, Tenant::query()->count());
    }

    public function test_a_bakery_can_be_created_from_a_backup_and_its_admin_reset(): void
    {
        // Un respaldo de otra panadería, con su propio administrador.
        $origen = AltaPanaderia::crear('origen', 'Origen', [], [
            'nombre' => 'Dueña', 'email' => 'duena@origen.test', 'password' => 'ClaveSegura2026',
        ]);
        $zip = $origen->run(fn () => Respaldo::exportar());

        $this->actingAs($this->superAdmin, 'superadmin')
            ->post('http://localhost/panel/panaderias', [
                'negocio' => 'Copia',
                'id' => 'copia',
                'modo' => 'respaldo',
                'respaldo' => new UploadedFile($zip, 'respaldo.zip', 'application/zip', UPLOAD_ERR_OK, true),
            ])
            ->assertRedirect('http://localhost/panel/panaderias/copia');

        $copia = Tenant::query()->findOrFail('copia');
        $this->assertSame(['duena@origen.test'], array_column(AltaPanaderia::administradores($copia), 'email'));

        // La dueña olvidó la contraseña: el superadministrador le pone una nueva.
        $this->post('http://localhost/panel/panaderias/copia/administrador', [
            'nombre' => 'Dueña',
            'email' => 'duena@origen.test',
            'password' => 'OtraClave2026x',
            'password_confirmation' => 'OtraClave2026x',
        ])->assertRedirect('http://localhost/panel/panaderias/copia');

        $copia->run(function () {
            $this->assertSame(1, User::query()->count());
            $this->assertTrue(Hash::check('OtraClave2026x', User::query()->firstOrFail()->password));
        });

        File::delete($zip);
    }

    public function test_https_certificates_are_only_allowed_for_known_domains(): void
    {
        AltaPanaderia::crear('luna', 'Luna', ['panaderialuna.com']);

        $permitido = fn (string $dominio) => $this->get('http://app:8080/interno/tls-permitido?domain='.$dominio)->status();

        $this->assertSame(200, $permitido('localhost'));
        $this->assertSame(200, $permitido('luna.localhost'));
        $this->assertSame(200, $permitido('panaderialuna.com'));
        $this->assertSame(404, $permitido('nadie.localhost'));
        $this->assertSame(404, $permitido('a.luna.localhost'));
        $this->assertSame(404, $permitido('ajeno.com'));

        // Desde afuera (llega por Caddy con la IP pública) no responde.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get('http://app:8080/interno/tls-permitido?domain=luna.localhost')
            ->assertNotFound();
    }

    public function test_deleting_a_bakery_requires_typing_its_id(): void
    {
        AltaPanaderia::crear('luna', 'Luna');
        $base = database_path('testing_panel_luna.sqlite');
        $this->assertFileExists($base);

        $this->actingAs($this->superAdmin, 'superadmin');

        $this->delete('http://localhost/panel/panaderias/luna', ['confirmacion' => 'otra'])
            ->assertSessionHasErrors('confirmacion');
        $this->assertNotNull(Tenant::query()->find('luna'));

        $this->delete('http://localhost/panel/panaderias/luna', ['confirmacion' => 'luna'])
            ->assertRedirect('http://localhost/panel/panaderias');
        $this->assertNull(Tenant::query()->find('luna'));
        $this->assertFileDoesNotExist($base);
    }
}
