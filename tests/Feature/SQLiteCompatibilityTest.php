<?php

namespace Tests\Feature;

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
        $this->assertTrue(Schema::hasColumn('cortes_caja', 'cerrado_por'));
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
