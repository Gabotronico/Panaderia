<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CorteCaja;
use App\Models\DetalleVenta;
use App\Models\Insumo;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ReportesExcelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private const PERIODO = ['fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31', 'tipo' => 'excel'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Rosa']);
        $this->admin->assignRole('Administrador');

        $categoria = Categoria::query()->create(['nombre' => 'Panes']);
        $marraqueta = Producto::query()->create(['categoria_id' => $categoria->id, 'nombre' => 'Marraqueta', 'precio_venta' => 0.5, 'stock' => 200, 'stock_minimo' => 50]);
        $torta = Producto::query()->create(['categoria_id' => $categoria->id, 'nombre' => 'Torta', 'precio_venta' => 120, 'stock' => 1, 'stock_minimo' => 2]);
        Insumo::query()->create(['nombre' => 'Harina', 'unidad_medida' => 'kg', 'cantidad_stock' => 100, 'stock_minimo' => 20, 'costo_unitario' => 6.2]);

        foreach ([
            ['V-1', '2026-10-05 08:00:00', 'efectivo', [[$marraqueta, 10]]],
            ['V-2', '2026-10-06 18:30:00', 'qr', [[$torta, 1], [$marraqueta, 4]]],
        ] as [$numero, $fecha, $pago, $items]) {
            $total = collect($items)->sum(fn ($i) => $i[0]->precio_venta * $i[1]);
            $venta = Venta::query()->create([
                'user_id' => $this->admin->id, 'numero_venta' => $numero, 'subtotal' => $total,
                'descuento' => 0, 'total' => $total, 'tipo_pago' => $pago, 'estado' => 'completada',
            ]);
            $venta->forceFill(['created_at' => $fecha])->save();
            foreach ($items as [$producto, $cantidad]) {
                DetalleVenta::query()->create([
                    'venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio_venta, 'subtotal' => $producto->precio_venta * $cantidad,
                ]);
            }
        }

        CorteCaja::query()->create([
            'user_id' => $this->admin->id, 'fecha_corte' => '2026-10-05', 'hora_apertura' => '06:30:00', 'hora_cierre' => '20:00:00',
            'monto_inicial' => 200, 'total_ventas' => 5, 'ventas_efectivo' => 5, 'ventas_qr' => 0,
            'total_efectivo' => 203, 'total_qr' => 0, 'estado' => 'cerrado', 'cerrado_por' => $this->admin->id,
        ]);
    }

    public function test_sales_report_downloads_as_excel_with_summary_sales_and_detail(): void
    {
        $libro = $this->libro($this->actingAs($this->admin)->get(route('reportes.ventas', self::PERIODO)), 'reporte-ventas-2026-10-01-a-2026-10-31.xlsx');

        $this->assertSame(['Resumen', 'Ventas', 'Detalle de productos'], $libro->getSheetNames());

        $ventas = $libro->getSheetByName('Ventas');
        $this->assertSame('N° de venta', $ventas->getCell('A5')->getValue());
        // Ordenadas de la más reciente a la más antigua, igual que en pantalla
        $this->assertSame('V-2', $ventas->getCell('A6')->getValue());
        $this->assertSame('QR', $ventas->getCell('D6')->getValue());
        $this->assertEquals(122, $ventas->getCell('I6')->getValue());
        $this->assertSame('=SUM(I6:I7)', $ventas->getCell('I8')->getValue());
        $this->assertEquals(127, $ventas->getCell('I8')->getCalculatedValue());

        $detalle = $libro->getSheetByName('Detalle de productos');
        $this->assertEquals(15, $detalle->getCell('D9')->getCalculatedValue()); // 1 + 4 + 10 unidades
    }

    public function test_best_selling_products_report_ranks_by_units(): void
    {
        $hoja = $this->libro($this->actingAs($this->admin)->get(route('reportes.productos-mas-vendidos', self::PERIODO)))->getSheetByName('Ranking');

        $this->assertSame('Marraqueta', $hoja->getCell('B6')->getValue());
        $this->assertEquals(14, $hoja->getCell('C6')->getValue());
        $this->assertSame('Torta', $hoja->getCell('B7')->getValue());
        $this->assertEqualsWithDelta(120 / 127, $hoja->getCell('E7')->getValue(), 0.0001);
        $this->assertSame('0.0%', $hoja->getStyle('E7')->getNumberFormat()->getFormatCode());
    }

    public function test_inventory_report_flags_low_stock(): void
    {
        $libro = $this->libro($this->actingAs($this->admin)->get(route('reportes.inventario', ['tipo' => 'excel'])));

        $this->assertSame(['Resumen', 'Productos', 'Insumos'], $libro->getSheetNames());
        $productos = $libro->getSheetByName('Productos');
        $this->assertSame('Torta', $productos->getCell('A7')->getValue());
        $this->assertSame('Stock bajo', $productos->getCell('G7')->getValue());
        $this->assertSame('"Bs "#,##0.00', $productos->getStyle('C6')->getNumberFormat()->getFormatCode());
        $this->assertEquals(620, $libro->getSheetByName('Insumos')->getCell('F6')->getValue());
    }

    public function test_cash_report_shows_the_count_result(): void
    {
        $hoja = $this->libro($this->actingAs($this->admin)->get(route('reportes.cortes-caja', self::PERIODO)))->getSheetByName('Cortes');

        $this->assertSame('Rosa', $hoja->getCell('B6')->getValue());
        $this->assertSame('06:30', $hoja->getCell('C6')->getValue());
        $this->assertEquals(205, $hoja->getCell('J6')->getValue()); // esperado: 200 inicial + 5 en efectivo
        $this->assertEquals(-2, $hoja->getCell('L6')->getValue());
        $this->assertSame('Faltante', $hoja->getCell('O6')->getValue());
    }

    private function libro(TestResponse $respuesta, ?string $archivo = null): Spreadsheet
    {
        $respuesta->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if ($archivo) {
            $this->assertStringContainsString($archivo, $respuesta->headers->get('Content-Disposition'));
        }

        $ruta = tempnam(sys_get_temp_dir(), 'reporte').'.xlsx';
        file_put_contents($ruta, $respuesta->streamedContent());
        $libro = IOFactory::load($ruta);
        unlink($ruta);

        return $libro;
    }
}
