<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\Empleado;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LatestMasterFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_register_closes_with_separate_cash_and_qr_totals_on_sqlite(): void
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
            'hora_apertura' => now()->subMinute()->format('H:i:s'),
            'monto_inicial' => 100,
            'estado' => 'abierto',
        ]);
        $register->forceFill([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ])->save();

        Venta::create([
            'user_id' => $cashier->id,
            'numero_venta' => 'TEST-EFECTIVO',
            'subtotal' => 40,
            'descuento' => 0,
            'total' => 40,
            'tipo_pago' => 'efectivo',
            'monto_recibido' => 40,
            'cambio' => 0,
            'estado' => 'completada',
        ]);
        Venta::create([
            'user_id' => $cashier->id,
            'numero_venta' => 'TEST-QR',
            'subtotal' => 60,
            'descuento' => 0,
            'total' => 60,
            'tipo_pago' => 'qr',
            'monto_recibido' => null,
            'cambio' => 0,
            'estado' => 'completada',
        ]);

        $response = $this->actingAs($admin)->put(route('cortes.update', $register), [
            'total_efectivo' => 140,
            'total_qr' => 60,
            'observaciones' => 'Cierre mixto',
        ]);

        $response->assertRedirect(route('cortes.show', $register));
        $this->assertDatabaseHas('cortes_caja', [
            'id' => $register->id,
            'estado' => 'cerrado',
            'cerrado_por' => $admin->id,
            'total_ventas' => 100,
            'ventas_efectivo' => 40,
            'ventas_qr' => 60,
            'total_efectivo' => 140,
            'total_qr' => 60,
            'diferencia' => 0,
            'diferencia_qr' => 0,
        ]);
        $this->assertTrue($register->fresh()->cuadra);
    }

    public function test_an_administrator_can_correct_and_delete_a_closed_register_on_sqlite(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $register = CorteCaja::create([
            'user_id' => $admin->id,
            'fecha_corte' => now()->toDateString(),
            'hora_apertura' => '08:00:00',
            'hora_cierre' => '16:00:00',
            'monto_inicial' => 100,
            'total_ventas' => 50,
            'ventas_efectivo' => 30,
            'ventas_qr' => 20,
            'total_efectivo' => 130,
            'total_qr' => 20,
            'monto_final' => 130,
            'diferencia' => 0,
            'diferencia_qr' => 0,
            'estado' => 'cerrado',
            'cerrado_por' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put(route('cortes.cierre.actualizar', $register), [
            'total_efectivo' => 135,
            'total_qr' => 19,
            'motivo' => 'Corrección por reconteo',
        ]);

        $response->assertRedirect(route('cortes.show', $register));
        $register->refresh();
        $this->assertSame('5.00', $register->diferencia);
        $this->assertSame('-1.00', $register->diferencia_qr);
        $this->assertStringContainsString('Corrección por reconteo', $register->observaciones);

        $this->delete(route('cortes.destroy', $register))
            ->assertRedirect(route('cortes.index'));
        $this->assertDatabaseMissing('cortes_caja', ['id' => $register->id]);
    }

    public function test_employee_schedules_calculate_lateness_and_shift_duration(): void
    {
        $employee = new Empleado([
            'hora_entrada' => '08:00',
            'hora_salida' => '16:00',
            'minutos_tolerancia' => 10,
        ]);

        $this->assertSame(5, $employee->calcularTardanza('08:15'));
        $this->assertSame(8.0, $employee->jornada_horas);

        $overnight = new Empleado([
            'hora_entrada' => '22:00',
            'hora_salida' => '06:00',
            'minutos_tolerancia' => 10,
        ]);

        $this->assertSame(10, $overnight->calcularTardanza('22:20'));
        $this->assertSame(8.0, $overnight->jornada_horas);
    }
}
