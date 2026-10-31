<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Identidad\Cuentas;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Tests\TestCase;

/**
 * Cuenta única: se inicia sesión una vez en el dominio central y después se
 * elige (o se salta) la panadería, con un pase de un solo uso.
 */
class EntradaCentralTest extends TestCase
{
    use RefreshDatabase;

    protected bool $conPanaderiaDePrueba = false;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => array_values(array_diff(
            config('tenancy.bootstrappers'),
            [DatabaseTenancyBootstrapper::class],
        ))]);

        URL::forceRootUrl('http://localhost');
    }

    private function panaderia(string $id, string $negocio): Tenant
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create(['id' => $id, 'negocio' => $negocio]));
        $tenant->domains()->create(['domain' => $id]);

        return $tenant;
    }

    private function usuario(Tenant $tenant, string $email, string $plano, string $rol = 'Administrador'): User
    {
        $user = $this->crearUsuario($tenant, $email, $plano, $rol);
        Cuentas::vincular($tenant, $user, $plano);

        return $user;
    }

    private function crearUsuario(Tenant $tenant, string $email, string $plano, string $rol = 'Administrador'): User
    {
        return $tenant->run(function () use ($tenant, $email, $plano, $rol): User {
            if (! \Spatie\Permission\Models\Role::query()->where('name', 'Administrador')->exists()) {
                app(RoleSeeder::class)->run();
            }

            $user = User::query()->create([
                'name' => 'Persona de '.$tenant->id,
                'email' => $email,
                'password' => $plano,
            ]);
            $user->assignRole($rol);

            return $user;
        });
    }

    /** En producción cada panadería tiene su base; en el test comparten tabla. */
    private function vincularA(Persona $persona, Tenant $tenant, User $user): void
    {
        DB::connection(config('tenancy.database.central_connection'))->table('persona_tenant')->insert([
            'persona_id' => $persona->id,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_con_una_sola_panaderia_entra_directo(): void
    {
        $alfa = $this->panaderia('alfa', 'Panadería Alfa');
        $user = $this->usuario($alfa, 'ana@alfa.test', 'Secreta123');

        $respuesta = $this->post('/cuenta/entrar', [
            'email' => 'ana@alfa.test',
            'password' => 'Secreta123',
        ]);

        $destino = $respuesta->headers->get('Location');
        $this->assertStringContainsString('/alfa/entrada/', (string) $destino);

        $this->get($destino)->assertRedirect('/alfa/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_con_varias_panaderias_pide_elegir(): void
    {
        $alfa = $this->panaderia('alfa', 'Panadería Alfa');
        $beta = $this->panaderia('beta', 'Panadería Beta');

        $this->usuario($alfa, 'ana@comun.test', 'Secreta123');
        $persona = Persona::deCorreo('ana@comun.test');
        $betaUser = $this->crearUsuario($beta, 'ana.beta@comun.test', 'Secreta123');
        $this->vincularA($persona, $beta, $betaUser);

        $this->post('/cuenta/entrar', [
            'email' => 'ana@comun.test',
            'password' => 'Secreta123',
        ])->assertRedirect('/cuenta/panaderia');

        $this->get('/cuenta/panaderia')
            ->assertOk()
            ->assertSee('Panadería Alfa')
            ->assertSee('Panadería Beta');

        $respuesta = $this->post('/cuenta/panaderia', ['tenant' => 'beta']);
        $this->get($respuesta->headers->get('Location'))->assertRedirect('/beta/home');

        $this->assertAuthenticatedAs($betaUser);
    }

    public function test_contrasena_incorrecta_no_revela_si_el_correo_existe(): void
    {
        $alfa = $this->panaderia('alfa', 'Panadería Alfa');
        $this->usuario($alfa, 'ana@alfa.test', 'Secreta123');

        $this->from('/cuenta/entrar')->post('/cuenta/entrar', [
            'email' => 'ana@alfa.test',
            'password' => 'incorrecta',
        ])->assertRedirect('/cuenta/entrar')->assertSessionHasErrors('email');

        $this->from('/cuenta/entrar')->post('/cuenta/entrar', [
            'email' => 'nadie@alfa.test',
            'password' => 'incorrecta',
        ])->assertRedirect('/cuenta/entrar')->assertSessionHasErrors('email');
    }

    public function test_el_pase_es_de_un_solo_uso_y_solo_para_su_panaderia(): void
    {
        $alfa = $this->panaderia('alfa', 'Panadería Alfa');
        $this->panaderia('beta', 'Panadería Beta');
        $this->usuario($alfa, 'ana@alfa.test', 'Secreta123');

        $destino = $this->post('/cuenta/entrar', [
            'email' => 'ana@alfa.test',
            'password' => 'Secreta123',
        ])->headers->get('Location');

        $this->get($destino)->assertRedirect('/alfa/home');

        // Ya usado: no vuelve a entrar (sesión limpia).
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->get($destino)->assertNotFound();

        // Y no sirve en otra panadería.
        $otro = $this->post('/cuenta/entrar', [
            'email' => 'ana@alfa.test',
            'password' => 'Secreta123',
        ])->headers->get('Location');

        $this->get(str_replace('/alfa/', '/beta/', (string) $otro))->assertNotFound();
    }

    public function test_crear_un_usuario_crea_su_cuenta_global(): void
    {
        $alfa = $this->panaderia('alfa', 'Panadería Alfa');
        $admin = $this->usuario($alfa, 'ana@alfa.test', 'Secreta123');

        $this->actingAs($admin)->post('/alfa/usuarios', [
            'name' => 'Cajera Nueva',
            'email' => 'caja@alfa.test',
            'password' => 'Caja123456',
            'password_confirmation' => 'Caja123456',
            'rol' => 'Administrador',
        ]);

        $persona = Persona::deCorreo('caja@alfa.test');

        $this->assertNotNull($persona);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Caja123456', $persona->password));
    }
}
