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

    /**
     * Semana de un panadero con horario 08:00–16:00 (8 h), 10 min de
     * tolerancia y Bs 600 semanales (Bs 100 el día).
     */
    public function test_days_below_the_schedule_only_pay_the_hours_worked(): void
    {
        [$user, $empleado] = $this->empleadoConHorario();

        $semana = [
            '2026-07-06' => ['presente', '08:00', '16:00'], // jornada completa: 1
            '2026-07-07' => ['presente', '08:00', '12:00'], // 4 de 8 h: 0,5
            '2026-07-08' => ['tardanza', '08:05', '16:00'], // 5 min, dentro de la tolerancia: 1
            '2026-07-09' => ['tardanza', '08:30', '16:00'], // 30 min menos 10 de tolerancia: 460/480
            '2026-07-10' => ['presente', null, null],       // sin marcaje, por estado: 1
            '2026-07-11' => ['presente', '07:00', '18:00'], // de más no suma: 1
        ];
        $this->registrarSemana($user, $empleado, $semana);

        $this->generarPlanillaSemanal($user);

        // 5 + 0,5 + 460/480 = 5,4583 días × Bs 100
        $this->assertDatabaseHas('planilla_empleado', [
            'empleado_id' => $empleado->id,
            'dias_pagados' => 5.46,
            'salario_bruto' => 545.83,
            'total_neto' => 545.83,
        ]);
    }

    public function test_a_full_week_on_schedule_pays_the_registered_salary(): void
    {
        [$user, $empleado] = $this->empleadoConHorario();

        $semana = [];
        foreach (['06', '07', '08', '09', '10', '11'] as $dia) {
            $semana["2026-07-{$dia}"] = ['presente', '08:00', '16:00'];
        }
        $this->registrarSemana($user, $empleado, $semana);

        $this->generarPlanillaSemanal($user);

        $this->assertDatabaseHas('planilla_empleado', [
            'empleado_id' => $empleado->id,
            'dias_pagados' => 6,
            'salario_bruto' => 600,
        ]);
    }

    /** @return array{0: User, 1: Empleado} */
    private function empleadoConHorario(): array
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $cargo = Cargo::query()->create(['nombre' => 'Panadero', 'descripcion' => 'Producción']);

        $empleado = Empleado::query()->create([
            'nombre' => 'Beto',
            'apellido' => 'Horario',
            'ci' => 'TEST-HORAS-1',
            'cargo_id' => $cargo->id,
            'salario_base' => 600,
            'tipo_pago' => 'semanal',
            'fecha_ingreso' => '2026-01-01',
            'hora_entrada' => '08:00',
            'hora_salida' => '16:00',
            'minutos_tolerancia' => 10,
            'activo' => true,
        ]);

        return [$user, $empleado];
    }

    private function registrarSemana(User $user, Empleado $empleado, array $semana): void
    {
        foreach ($semana as $fecha => [$estado, $entrada, $salida]) {
            Asistencia::query()->create([
                'empleado_id' => $empleado->id,
                'fecha' => $fecha,
                'estado' => $estado,
                'hora_entrada' => $entrada,
                'hora_salida' => $salida,
                'user_id' => $user->id,
            ]);
        }
    }

    private function generarPlanillaSemanal(User $user): void
    {
        $this->actingAs($user)->post(route('planillas.store'), [
            'tipo' => 'semanal',
            'periodo_inicio' => '2026-07-06',
            'periodo_fin' => '2026-07-11',
        ])->assertSessionHas('success');

        $this->get(route('planillas.show', Planilla::query()->firstOrFail()))->assertOk();
    }
}
