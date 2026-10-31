<?php

namespace Tests\Feature;

use App\Models\TenantLicencia;
use App\Models\User;
use App\Support\Licencias\Licencia;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Licencias por panadería: cupos del plan, vigencia, suspensión y el endpoint
 * firmado que usa el panel central de INC Group.
 */
class LicenciaTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'secreto-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        Licencia::olvidar();
        config(['licencias.secreto' => self::SECRETO]);
    }

    private function licencia(array $datos = []): TenantLicencia
    {
        return TenantLicencia::query()->create(array_merge([
            'tenant_id' => 'prueba',
            'status' => 'active',
            'plan' => 'basico',
            'max_admins' => 1,
            'max_cajeros' => 2,
            'valid_until' => now()->addMonth(),
            'version' => 1,
            'synced_at' => now(),
        ], $datos));
    }

    private function cajeros(int $cantidad): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            $usuario = User::query()->create([
                'name' => "Cajero {$i}",
                'email' => "cajero{$i}@prueba.test",
                'password' => 'Password123',
            ]);
            $usuario->assignRole('Cajero');
        }
    }

    private function administrador(): User
    {
        app(RoleSeeder::class)->run();

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@prueba.test',
            'password' => 'Password123',
        ]);
        $admin->assignRole('Administrador');

        return $admin;
    }

    /** Encabezados de firma, con el prefijo que espera el cliente de tests. */
    private function firma(string $metodo, string $ruta, string $cuerpo = ''): array
    {
        $timestamp = (string) time();
        $firma = hash_hmac(
            'sha256',
            $timestamp.'.'.$metodo.'.'.$ruta.'.'.hash('sha256', $cuerpo),
            self::SECRETO,
        );

        return [
            'HTTP_X_INC_TIMESTAMP' => $timestamp,
            'HTTP_X_INC_SIGNATURE' => $firma,
        ];
    }

    public function test_sin_licencia_no_se_limita_el_alta_de_usuarios(): void
    {
        app(RoleSeeder::class)->run();
        $this->cajeros(3);

        $this->assertSame('sin_licencia', Licencia::estado('prueba'));
        $this->assertNull(Licencia::bloqueoPorRol('Cajero'));
    }

    public function test_el_plan_limita_los_cajeros(): void
    {
        app(RoleSeeder::class)->run();
        $this->licencia();
        $this->cajeros(2);

        $mensaje = Licencia::bloqueoPorRol('Cajero');

        $this->assertNotNull($mensaje);
        $this->assertStringContainsString('permite 2 cajeros', $mensaje);
    }

    public function test_el_plan_ilimitado_no_tiene_tope(): void
    {
        app(RoleSeeder::class)->run();
        $this->licencia(['plan' => 'ilimitado', 'max_admins' => null, 'max_cajeros' => null]);
        $this->cajeros(6);

        $this->assertNull(Licencia::bloqueoPorRol('Cajero'));
        $this->assertNull(Licencia::bloqueoPorRol('Administrador'));
    }

    public function test_una_licencia_vencida_deja_la_panaderia_solo_en_lectura(): void
    {
        $this->licencia(['valid_until' => now()->subDay()]);
        $admin = $this->administrador();

        $this->assertSame('expired', Licencia::estado('prueba'));

        $this->actingAs($admin)->get('/prueba/usuarios')->assertOk();

        $this->actingAs($admin)
            ->post('/prueba/usuarios', ['name' => 'Otro'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'cajero@prueba.test']);
    }

    public function test_una_licencia_suspendida_bloquea_el_acceso(): void
    {
        $this->licencia(['status' => 'suspended']);
        $admin = $this->administrador();

        $this->actingAs($admin)
            ->get('/prueba/usuarios')
            ->assertStatus(403)
            ->assertSee('suspendido');
    }

    public function test_el_endpoint_interno_aplica_la_licencia(): void
    {
        // En producción estas rutas viven en el dominio central, sin panadería
        // iniciada; en los tests el TestCase deja "prueba" abierta.
        tenancy()->end();
        Licencia::olvidar();

        $datos = [
            'external_tenant_ref' => 'prueba',
            'external_license_id' => '00000000-0000-4000-8000-000000000001',
            'status' => 'active',
            'plan' => 'pro',
            'max_admins' => 2,
            'max_cajeros' => 6,
            'valid_until' => now()->addMonth()->toIso8601String(),
            'version' => 7,
        ];
        $cuerpo = json_encode($datos);

        $this->call('POST', '/interno/licencia', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ] + $this->firma('POST', '/interno/licencia', $cuerpo), $cuerpo)
            ->assertOk()
            ->assertJson(['aplicada' => true, 'tenant' => 'prueba', 'version' => 7]);

        $this->assertDatabaseHas('tenant_licenses', [
            'tenant_id' => 'prueba',
            'plan' => 'pro',
            'max_cajeros' => 6,
            'version' => 7,
        ]);
    }

    public function test_el_endpoint_interno_rechaza_una_firma_invalida(): void
    {
        tenancy()->end();

        $cuerpo = json_encode(['external_tenant_ref' => 'prueba']);

        $this->call('POST', '/interno/licencia', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_INC_TIMESTAMP' => (string) time(),
            'HTTP_X_INC_SIGNATURE' => str_repeat('a', 64),
        ], $cuerpo)->assertStatus(401);
    }

    public function test_una_version_vieja_no_pisa_la_licencia(): void
    {
        $this->licencia(['version' => 9, 'plan' => 'pro']);
        tenancy()->end();
        Licencia::olvidar();

        $datos = [
            'external_tenant_ref' => 'prueba',
            'status' => 'active',
            'plan' => 'basico',
            'max_admins' => 1,
            'max_cajeros' => 2,
            'valid_until' => now()->addMonth()->toIso8601String(),
            'version' => 3,
        ];
        $cuerpo = json_encode($datos);

        $this->call('POST', '/interno/licencia', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ] + $this->firma('POST', '/interno/licencia', $cuerpo), $cuerpo)
            ->assertOk()
            ->assertJson(['aplicada' => false, 'version' => 9]);

        $this->assertDatabaseHas('tenant_licenses', ['tenant_id' => 'prueba', 'plan' => 'pro']);
    }

    public function test_el_endpoint_de_uso_lista_las_panaderias(): void
    {
        $this->licencia();
        tenancy()->end();
        Licencia::olvidar();

        $this->get('/interno/licencias', $this->firma('GET', '/interno/licencias'))
            ->assertOk()
            ->assertJsonPath('tenants.0.tenant', 'prueba')
            ->assertJsonPath('tenants.0.licencia.plan', 'basico');
    }
}
