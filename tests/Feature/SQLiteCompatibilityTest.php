<?php

namespace Tests\Feature;

use App\Models\CorteCaja;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SQLiteCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_schema_is_ready_for_sqlite(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(1, (int) DB::selectOne('PRAGMA foreign_keys')->foreign_keys);

        $this->assertTrue(Schema::hasColumns('productos', [
            'categoria_id',
            'precio_venta',
            'stock',
            'stock_minimo',
        ]));
        $this->assertFalse(Schema::hasColumn('productos', 'rendimiento_receta'));
        $this->assertTrue(Schema::hasColumns('recetas', ['nombre', 'rendimiento']));
        $this->assertTrue(Schema::hasColumns('asistencias', ['fecha', 'estado', 'hora_salida']));
        $this->assertTrue(Schema::hasColumns('planilla_empleado', [
            'planilla_id',
            'empleado_id',
            'total_neto',
        ]));
        $this->assertTrue(Schema::hasColumns('cortes_caja', [
            'cerrado_por', 'ventas_efectivo', 'ventas_qr', 'total_qr', 'diferencia_qr',
        ]));
        $this->assertTrue(Schema::hasColumns('empleados', [
            'hora_entrada', 'hora_salida', 'minutos_tolerancia',
        ]));

        $this->assertSame(0, DB::table('cortes_caja')->sum('ventas_qr'));
        $this->assertSame(0, DB::table('cortes_caja')->sum('total_qr'));
    }

    public function test_latest_migrations_upgrade_existing_sqlite_data_and_can_roll_back(): void
    {
        $qrMigration = require database_path(
            'migrations/2026_07_29_100001_add_qr_to_cortes_caja_table.php'
        );
        $scheduleMigration = require database_path(
            'migrations/2026_07_29_200001_add_horario_to_empleados_table.php'
        );

        $scheduleMigration->down();
        $qrMigration->down();

        $this->assertFalse(Schema::hasColumn('cortes_caja', 'ventas_qr'));
        $this->assertFalse(Schema::hasColumn('empleados', 'hora_entrada'));

        $user = User::factory()->create();
        $register = CorteCaja::create([
            'user_id' => $user->id,
            'fecha_corte' => now()->toDateString(),
            'hora_apertura' => '08:00:00',
            'hora_cierre' => '16:00:00',
            'monto_inicial' => 100,
            'total_ventas' => 75,
            'total_efectivo' => 175,
            'monto_final' => 175,
            'diferencia' => 0,
            'estado' => 'cerrado',
        ]);

        $qrMigration->up();
        $scheduleMigration->up();

        $this->assertTrue(Schema::hasColumns('cortes_caja', [
            'ventas_efectivo', 'ventas_qr', 'total_qr', 'diferencia_qr',
        ]));
        $this->assertTrue(Schema::hasColumns('empleados', [
            'hora_entrada', 'hora_salida', 'minutos_tolerancia',
        ]));
        $this->assertSame(
            75.0,
            (float) DB::table('cortes_caja')->where('id', $register->id)->value('ventas_efectivo')
        );

        $integrity = DB::selectOne('PRAGMA integrity_check');
        $this->assertSame('ok', $integrity->integrity_check);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_query_heavy_pages_render_with_sqlite(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrador');
        $this->actingAs($user);

        $period = [
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-31',
        ];

        $urls = [
            route('home'),
            route('finanzas.index', ['year' => 2026, 'month' => 7]),
            route('gastos-pagos.index', ['year' => 2026, 'month' => 7]),
            route('gastos-pagos.anual', ['year' => 2026]),
            route('planillas.index'),
            route('usuarios.index'),
            route('reportes.index'),
            route('reportes.ventas', $period),
            route('reportes.productos-mas-vendidos', $period),
            route('reportes.inventario'),
            route('reportes.cortes-caja', $period),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $integrity = DB::selectOne('PRAGMA integrity_check');
        $this->assertSame('ok', $integrity->integrity_check);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }
}
