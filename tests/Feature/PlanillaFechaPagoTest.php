<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * El costo de la planilla pesa en el mes en que se PAGÓ, no en el que arranca
 * ni en el que termina el período trabajado.
 *
 * El caso que motiva todo esto es la semana del lunes 31/08/2026 al sábado
 * 05/09/2026: solo el lunes es de agosto, se cobra el sábado, y por lo tanto
 * es costo de septiembre.
 */
class PlanillaFechaPagoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    /** Empleado semanal de Bs 600, o sea Bs 100 por día laborable. */
    private function empleado(string $ci = 'TEST-FP-1'): Empleado
    {
        $cargo = Cargo::query()->firstOrCreate(
            ['nombre' => 'Panadero'],
            ['descripcion' => 'Produccion']
        );

        return Empleado::query()->create([
            'nombre'        => 'Ana',
            'apellido'      => 'Prueba',
            'ci'            => $ci,
            'cargo_id'      => $cargo->id,
            'salario_base'  => 600,
            'tipo_pago'     => 'semanal',
            'fecha_ingreso' => '2026-01-01',
            'activo'        => true,
        ]);
    }

    /** Semana completa lunes 31/08 al sábado 05/09, con los 6 días presentes. */
    private function planillaQueCruzaElMes(Empleado $empleado): Planilla
    {
        $dias = ['2026-08-31', '2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04', '2026-09-05'];

        foreach ($dias as $fecha) {
            Asistencia::query()->create([
                'empleado_id' => $empleado->id,
                'fecha'       => $fecha,
                'estado'      => 'presente',
                'user_id'     => $this->admin->id,
            ]);
        }

        $this->actingAs($this->admin)->post(route('planillas.store'), [
            'tipo'           => 'semanal',
            'periodo_inicio' => '2026-08-31',
            'periodo_fin'    => '2026-09-05',
        ])->assertRedirect(route('planillas.index'));

        return Planilla::query()->firstOrFail();
    }

    /** Sueldos que Finanzas imputa al mes indicado. */
    private function planillasDelMes(int $year, int $month): float
    {
        $respuesta = $this->actingAs($this->admin)
            ->get(route('finanzas.index', ['year' => $year, 'month' => $month]))
            ->assertOk();

        return (float) $respuesta->viewData('actual')['planillas'];
    }

    public function test_la_semana_que_cruza_el_mes_se_carga_al_mes_del_sabado_de_pago(): void
    {
        Carbon::setTestNow('2026-09-05');

        $empleado = $this->empleado();
        $planilla = $this->planillaQueCruzaElMes($empleado);

        $this->assertSame('600.00', (string) $planilla->total_general,
            'La semana completa paga el salario semanal integro.');

        $this->actingAs($this->admin)->post(route('planillas.cerrar', $planilla));
        $this->actingAs($this->admin)->post(route('planillas.pagar', $planilla), [
            'fecha_pago' => '2026-09-05',
        ])->assertSessionHas('success');

        $planilla->refresh();
        $this->assertSame('pagada', $planilla->estado);
        $this->assertSame('2026-09-05', $planilla->fecha_pago->toDateString());

        // Lo que pedía el cliente: el sueldo pesa en septiembre, no en agosto,
        // aunque el período haya arrancado el lunes 31 de agosto.
        $this->assertSame(0.0, $this->planillasDelMes(2026, 8),
            'Agosto no debe cargar un sueldo que se pago en septiembre.');
        $this->assertSame(600.0, $this->planillasDelMes(2026, 9),
            'Septiembre debe cargar el sueldo pagado el sabado 05/09.');
    }

    public function test_sin_fecha_indicada_se_toma_el_cierre_del_periodo(): void
    {
        Carbon::setTestNow('2026-09-08');

        $planilla = $this->planillaQueCruzaElMes($this->empleado());

        $this->actingAs($this->admin)->post(route('planillas.cerrar', $planilla));
        $this->actingAs($this->admin)->post(route('planillas.pagar', $planilla));

        $planilla->refresh();
        $this->assertSame('2026-09-05', $planilla->fecha_pago->toDateString(),
            'Por defecto se asume el sabado en que cierra el periodo.');
    }

    public function test_no_se_acepta_una_fecha_de_pago_futura(): void
    {
        Carbon::setTestNow('2026-09-02');

        $planilla = $this->planillaQueCruzaElMes($this->empleado());
        $this->actingAs($this->admin)->post(route('planillas.cerrar', $planilla));

        $this->actingAs($this->admin)->post(route('planillas.pagar', $planilla), [
            'fecha_pago' => '2026-09-20',
        ])->assertSessionHasErrors('fecha_pago');

        $planilla->refresh();
        $this->assertSame('cerrada', $planilla->estado,
            'Una fecha invalida no debe dejar la planilla marcada como pagada.');
        $this->assertNull($planilla->fecha_pago);
    }

    public function test_no_se_acepta_pagar_antes_de_que_empiece_el_periodo(): void
    {
        Carbon::setTestNow('2026-09-05');

        $planilla = $this->planillaQueCruzaElMes($this->empleado());
        $this->actingAs($this->admin)->post(route('planillas.cerrar', $planilla));

        $this->actingAs($this->admin)->post(route('planillas.pagar', $planilla), [
            'fecha_pago' => '2026-08-20',
        ])->assertSessionHasErrors('fecha_pago');

        $this->assertSame('cerrada', $planilla->refresh()->estado);
    }

    public function test_una_planilla_pagada_con_retraso_cuenta_en_el_mes_del_desembolso(): void
    {
        Carbon::setTestNow('2026-10-03');

        $planilla = $this->planillaQueCruzaElMes($this->empleado());
        $this->actingAs($this->admin)->post(route('planillas.cerrar', $planilla));

        // Se trabajó agosto/septiembre pero la plata recién salió en octubre.
        $this->actingAs($this->admin)->post(route('planillas.pagar', $planilla), [
            'fecha_pago' => '2026-10-03',
        ])->assertSessionHas('success');

        $this->assertSame(0.0, $this->planillasDelMes(2026, 9));
        $this->assertSame(600.0, $this->planillasDelMes(2026, 10));
    }

    /**
     * La pantalla de la planilla ganó dos bloques nuevos: el selector de fecha
     * cuando está cerrada y el aviso del mes imputado cuando ya se pagó. Un
     * error de Blade ahí solo aparecería al abrir la pantalla, no al calcular.
     */
    public function test_la_pantalla_muestra_el_selector_de_fecha_y_luego_el_mes_imputado(): void
    {
        Carbon::setTestNow('2026-09-05');

        $planilla = $this->planillaQueCruzaElMes($this->empleado());
        $this->actingAs($this->admin)->post(route('planillas.cerrar', $planilla));

        $this->actingAs($this->admin)->get(route('planillas.show', $planilla))
            ->assertOk()
            ->assertSee('name="fecha_pago"', false)
            ->assertSee('value="2026-09-05"', false);

        $this->actingAs($this->admin)->post(route('planillas.pagar', $planilla), [
            'fecha_pago' => '2026-09-05',
        ]);

        $this->actingAs($this->admin)->get(route('planillas.show', $planilla))
            ->assertOk()
            ->assertSee('Pagada el 05/09/2026')
            ->assertSee('cuenta en Septiembre');
    }

    public function test_mientras_no_este_pagada_no_pesa_en_ningun_mes(): void
    {
        Carbon::setTestNow('2026-09-05');

        $planilla = $this->planillaQueCruzaElMes($this->empleado());

        $this->assertSame(0.0, $this->planillasDelMes(2026, 8));
        $this->assertSame(0.0, $this->planillasDelMes(2026, 9));

        $this->actingAs($this->admin)->post(route('planillas.cerrar', $planilla));

        $this->assertSame(0.0, $this->planillasDelMes(2026, 9),
            'Cerrada sigue siendo un calculo: la plata todavia no salio.');
    }
}
