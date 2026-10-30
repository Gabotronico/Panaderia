<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Cada panadería vive bajo su id (obrador.incgroupbo.com/luna). Una URL
 * armada a mano desde la raíz ("/insumos/3/comprar") cae fuera de la
 * panadería y da 404: pasó en producción con los modales que fijan el action
 * de su formulario por JavaScript.
 */
class RutasDentroDeLaPanaderiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_views_do_not_build_root_relative_urls_by_hand(): void
    {
        $sospechosas = [];

        foreach (File::allFiles(resource_path('views')) as $vista) {
            foreach (explode("\n", $vista->getContents()) as $numero => $linea) {
                // action = '/algo', href = `/algo`, fetch('/algo'), location = "/algo"
                if (preg_match('/(action|href|location|fetch\()\s*[=(]?\s*[\'"`]\/[a-z]/i', $linea)
                    && ! str_contains($linea, 'route(')) {
                    $sospechosas[] = $vista->getRelativePathname().':'.($numero + 1).' '.trim($linea);
                }
            }
        }

        $this->assertSame([], $sospechosas, 'Usá route() para que la URL quede dentro de la panadería.');
    }

    public function test_the_supplies_list_posts_purchases_inside_the_bakery(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        Insumo::query()->create([
            'nombre' => 'Harina',
            'unidad_medida' => 'kg',
            'cantidad_stock' => 10,
            'stock_minimo' => 1,
            'costo_unitario' => 5,
        ]);

        $this->actingAs($admin)
            ->get(route('insumos.index'))
            ->assertOk()
            // @json escapa las barras: "\/prueba\/insumos\/..." es la misma URL en JS.
            ->assertSee(json_encode(route('insumos.comprar', '__ID__')), false)
            ->assertSee(json_encode(route('insumos.merma', '__ID__')), false);

        $this->assertSame('http://localhost/prueba/insumos/__ID__/comprar', route('insumos.comprar', '__ID__'));
    }
}
