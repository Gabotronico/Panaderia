<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClosedRegisterPermissionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_upgrade_grants_closed_register_permissions_to_an_existing_administrator(): void
    {
        $this->seed(RoleSeeder::class);

        $administrator = User::factory()->create();
        $administrator->assignRole('Administrador');

        $register = CorteCaja::query()->create([
            'user_id' => $administrator->id,
            'fecha_corte' => now()->toDateString(),
            'hora_apertura' => '08:00:00',
            'hora_cierre' => '16:00:00',
            'monto_inicial' => 100,
            'total_ventas' => 50,
            'ventas_efectivo' => 50,
            'ventas_qr' => 0,
            'total_efectivo' => 150,
            'total_qr' => 0,
            'monto_final' => 150,
            'diferencia' => 0,
            'diferencia_qr' => 0,
            'estado' => 'cerrado',
            'cerrado_por' => $administrator->id,
        ]);

        $migration = require database_path(
            'migrations/2026_07_31_200001_add_closed_register_permissions.php'
        );

        // Simula una base creada antes de que existieran estos permisos.
        $migration->down();
        $this->refreshPermissionState($administrator);

        $this->assertFalse($administrator->can('editar-cortes-cerrados'));
        $this->assertFalse($administrator->can('eliminar-cortes-cerrados'));
        $this->actingAs($administrator)
            ->get(route('cortes.show', $register))
            ->assertOk()
            ->assertDontSee('Eliminar Cierre');

        $migration->up();
        $this->refreshPermissionState($administrator);

        $this->assertTrue($administrator->can('editar-cortes-cerrados'));
        $this->assertTrue($administrator->can('eliminar-cortes-cerrados'));
        $this->assertDatabaseHas('permissions', [
            'name' => 'eliminar-cortes-cerrados',
            'guard_name' => 'web',
        ]);
        $this->actingAs($administrator)
            ->get(route('cortes.show', $register))
            ->assertOk()
            ->assertSee('Eliminar Cierre');
    }

    private function refreshPermissionState(User $user): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }
}
