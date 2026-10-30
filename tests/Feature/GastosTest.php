<?php

namespace Tests\Feature;

use App\Models\GastoFijo;
use App\Models\GastoPago;
use App\Models\GastoVariable;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GastosTest extends TestCase
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

    /** El bono de alimentación se paga cada sábado: octubre de 2026 tiene cinco. */
    public function test_a_weekly_fixed_expense_generates_one_payment_per_week(): void
    {
        $this->actingAs($this->admin)
            ->post(route('gastos-fijos.store'), [
                'nombre' => 'Bono de alimentación',
                'categoria' => 'personal',
                'monto_estimado' => 150,
                'frecuencia' => 'semanal',
                'dia_semana' => 6,
            ])
            ->assertRedirect(route('gastos-fijos.index'));

        $bono = GastoFijo::query()->firstOrFail();
        $this->assertTrue($bono->esSemanal());
        $this->assertSame('Cada sábado', $bono->vencimiento_texto);
        $this->assertSame(750.0, $bono->estimadoEnMes(2026, 10));

        GastoFijo::query()->create([
            'nombre' => 'Alquiler', 'categoria' => 'alquiler', 'monto_estimado' => 300,
            'frecuencia' => 'mensual', 'dia_vencimiento' => 5, 'mes_inicio' => 1,
            'user_id' => $this->admin->id,
        ]);

        $this->post(route('gastos-pagos.generar'), ['year' => 2026, 'month' => 10])
            ->assertSessionHas('success');

        $this->assertSame(
            ['2026-10-03', '2026-10-10', '2026-10-17', '2026-10-24', '2026-10-31'],
            $bono->pagos()->orderBy('fecha_vencimiento')->get()->map(fn ($p) => $p->fecha_vencimiento->toDateString())->all()
        );
        $this->assertSame(6, GastoPago::query()->where('periodo', '2026-10')->count());

        // "Agregar faltantes" no duplica lo que ya está.
        $this->post(route('gastos-pagos.generar'), ['year' => 2026, 'month' => 10]);
        $this->assertSame(6, GastoPago::query()->count());

        $this->get(route('gastos-pagos.index', ['year' => 2026, 'month' => 10]))->assertOk()->assertSee('semanal');
        $this->get(route('gastos-fijos.index'))->assertOk()->assertSee('Cada sábado');
    }

    public function test_paid_weeks_add_up_in_the_month_and_in_the_annual_matrix(): void
    {
        $bono = GastoFijo::query()->create([
            'nombre' => 'Bono de alimentación', 'categoria' => 'personal', 'monto_estimado' => 150,
            'frecuencia' => 'semanal', 'dia_semana' => 6, 'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('gastos-pagos.generar'), ['year' => 2026, 'month' => 10]);

        foreach ($bono->pagos()->orderBy('fecha_vencimiento')->take(2)->get() as $pago) {
            $this->patch(route('gastos-pagos.pagar', $pago), [
                'monto_real' => 150,
                'fecha_pago' => $pago->fecha_vencimiento->toDateString(),
            ])->assertSessionHas('success');
        }

        // Dos semanas pagadas de cinco: Bs 300 de gasto fijo en el mes.
        $this->get(route('finanzas.index', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertViewHas('actual', fn (array $actual) => $actual['gastos_fijos'] === 300.0)
            ->assertSee('Personal y beneficios');

        $this->get(route('gastos-pagos.anual', ['year' => 2026]))
            ->assertOk()
            ->assertViewHas('totalesMes', fn (array $totales) => $totales[10] === 300.0)
            ->assertSee('2/5 sem.');
    }

    public function test_a_weekly_expense_needs_its_weekday_and_a_monthly_one_its_day(): void
    {
        $base = ['nombre' => 'Bono', 'categoria' => 'personal', 'monto_estimado' => 100];

        $this->actingAs($this->admin)
            ->post(route('gastos-fijos.store'), $base + ['frecuencia' => 'semanal'])
            ->assertSessionHasErrors('dia_semana');

        $this->post(route('gastos-fijos.store'), $base + ['frecuencia' => 'mensual'])
            ->assertSessionHasErrors('dia_vencimiento');

        $this->assertSame(0, GastoFijo::query()->count());
    }

    public function test_the_financial_summary_breaks_variable_expenses_down_by_category(): void
    {
        foreach ([
            ['2026-10-02', 'Flete de harina', 'transporte', 400],
            ['2026-10-09', 'Taxi', 'transporte', 100.50],
            ['2026-10-10', 'Detergente', 'limpieza', 80],
            ['2026-09-30', 'Arreglo del horno', 'mantenimiento', 999],
        ] as [$fecha, $concepto, $categoria, $monto]) {
            GastoVariable::query()->create(compact('fecha', 'concepto', 'categoria', 'monto') + ['user_id' => $this->admin->id]);
        }

        $this->actingAs($this->admin)
            ->get(route('finanzas.index', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertViewHas('variablesPorCategoria', function ($categorias) {
                // De mayor a menor, solo las de octubre.
                return $categorias->pluck('categoria')->all() === ['transporte', 'limpieza']
                    && (float) $categorias[0]->total === 500.50
                    && (int) $categorias[0]->cantidad === 2;
            })
            ->assertSee('Gastos variables por categoría')
            ->assertSee('Transporte y fletes')
            ->assertSee('Limpieza e higiene')
            ->assertDontSee('Mantenimiento y reparaciones');
    }
}
