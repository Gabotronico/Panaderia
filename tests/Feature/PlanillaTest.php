<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanillaTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_generate_a_payroll_with_sqlite(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $cargo = Cargo::query()->create([
            'nombre' => 'Panadero',
            'descripcion' => 'Producción',
        ]);

        $empleado = Empleado::query()->create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'ci' => 'TEST-PLANILLA-1',
            'cargo_id' => $cargo->id,
            'salario_base' => 600,
            'tipo_pago' => 'semanal',
            'fecha_ingreso' => '2026-01-01',
            'activo' => true,
        ]);

        foreach (['2026-07-05', '2026-07-06'] as $fecha) {
            Asistencia::query()->create([
                'empleado_id' => $empleado->id,
                'fecha' => $fecha,
                'estado' => 'presente',
                'user_id' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->post(route('planillas.store'), [
            'tipo' => 'semanal',
            'periodo_inicio' => '2026-07-05',
            'periodo_fin' => '2026-07-11',
        ]);

        $response->assertRedirect(route('planillas.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('planilla_empleado', [
            'empleado_id' => $empleado->id,
            'dias_trabajados' => 1,
            'salario_bruto' => 100,
            'total_neto' => 100,
        ]);

        $planilla = Planilla::query()->firstOrFail();
        $this->get(route('planillas.show', $planilla))->assertOk();
        $this->get(route('planillas.pdf', $planilla))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
