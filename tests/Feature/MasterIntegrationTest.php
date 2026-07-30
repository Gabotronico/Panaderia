<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MasterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_user_on_sqlite(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Segundo administrador',
            'email' => 'segundo.admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'Administrador',
        ]);

        $response->assertRedirect(route('usuarios.index'));

        $createdUser = User::where('email', 'segundo.admin@example.com')->firstOrFail();
        $this->assertTrue($createdUser->hasRole('Administrador'));
    }

    public function test_an_administrator_can_close_another_users_register_on_sqlite(): void
    {
        Mail::fake();
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $cashier = User::factory()->create();
        $cashier->assignRole('Cajero');

        $register = CorteCaja::create([
            'user_id' => $cashier->id,
            'fecha_corte' => now()->toDateString(),
            'hora_apertura' => now()->format('H:i:s'),
            'monto_inicial' => 100,
            'estado' => 'abierto',
        ]);

        $response = $this->actingAs($admin)->put(route('cortes.update', $register), [
            'total_efectivo' => 150,
            'total_qr' => 0,
            'observaciones' => 'Cierre de prueba',
        ]);

        $response->assertRedirect(route('cortes.show', $register));
        $this->assertDatabaseHas('cortes_caja', [
            'id' => $register->id,
            'estado' => 'cerrado',
            'cerrado_por' => $admin->id,
            'total_ventas' => 0,
            'total_efectivo' => 150,
            'diferencia' => 50,
        ]);

        $this->assertTrue($register->fresh()->cerradoPor->is($admin));
    }
}
