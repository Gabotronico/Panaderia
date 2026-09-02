<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\CorteCaja;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El cajero perdió el permiso ver-ventas (no consulta el historial), pero
 * conserva crear-ventas: puede registrar tanto la venta normal como la
 * directa. El único link a Venta Directa vivía dentro del listado, así que al
 * quitarle ver-ventas el cajero se quedó sin ninguna forma de llegar ahí
 * aunque la ruta seguía abierta para él. Esto verifica que las dos formas de
 * vender siguen siendo alcanzables y que ningún link intermedio lo manda al
 * listado, que le da 403.
 */
class CajeroVentaDirectaTest extends TestCase
{
    use RefreshDatabase;

    private User $cajero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $almacen = Almacen::create(['nombre' => 'Sucursal Test', 'activo' => true]);

        $this->cajero = User::factory()->create(['almacen_id' => $almacen->id]);
        $this->cajero->assignRole('Cajero');

        CorteCaja::create([
            'user_id'       => $this->cajero->id,
            'fecha_corte'   => now()->toDateString(),
            'hora_apertura' => now()->format('H:i:s'),
            'monto_inicial' => 100,
            'estado'        => 'abierto',
        ]);
    }

    public function test_el_cajero_no_puede_ver_el_listado_de_ventas(): void
    {
        $this->actingAs($this->cajero)->get(route('ventas.index'))->assertForbidden();
    }

    public function test_el_cajero_puede_abrir_el_formulario_de_venta_directa(): void
    {
        $this->actingAs($this->cajero)->get(route('ventas.directa'))->assertOk();
    }

    public function test_el_cajero_puede_abrir_el_formulario_de_venta_normal(): void
    {
        $this->actingAs($this->cajero)->get(route('ventas.create'))->assertOk();
    }

    public function test_el_menu_del_cajero_incluye_los_dos_links_de_venta(): void
    {
        $html = $this->actingAs($this->cajero)->get(route('ventas.create'))->getContent();

        $this->assertStringContainsString(route('ventas.create'), $html);
        $this->assertStringContainsString(route('ventas.directa'), $html);
    }

    public function test_volver_desde_venta_directa_no_manda_al_cajero_al_listado(): void
    {
        $html = $this->actingAs($this->cajero)->get(route('ventas.directa'))->getContent();

        // route('ventas.index') es "/ventas", que es prefijo de "/ventas/create"
        // y de "/ventas/directa": no alcanza con buscarlo como substring, hay
        // que verificar que no aparece como destino exacto de un href.
        $this->assertStringNotContainsString('href="'.route('ventas.index').'"', $html);
        $this->assertStringContainsString(route('ventas.create'), $html);
    }

    public function test_cancelar_desde_venta_normal_no_manda_al_cajero_al_listado(): void
    {
        $html = $this->actingAs($this->cajero)->get(route('ventas.create'))->getContent();

        $this->assertStringNotContainsString('href="'.route('ventas.index').'"', $html);
    }

    public function test_el_cajero_puede_registrar_una_venta_directa(): void
    {
        $this->actingAs($this->cajero)->post(route('ventas.directa.store'), [
            'efectivo' => 50,
        ])->assertRedirect(route('ventas.create'));

        $this->assertDatabaseHas('ventas', [
            'user_id' => $this->cajero->id,
            'total'   => 50,
        ]);
    }
}
