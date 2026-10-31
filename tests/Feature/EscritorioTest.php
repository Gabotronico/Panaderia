<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pantalla de arranque de la app de escritorio: código de panadería y salto al
 * servidor. En el navegador esas rutas no existen.
 */
class EscritorioTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->archivo = 'escritorio-test-'.uniqid().'.txt';

        config([
            'escritorio.url_base' => 'https://obrador.incgroupbo.com',
            'escritorio.archivo' => $this->archivo,
            'nativephp-internal.running' => true,
        ]);

        // En producción estas rutas viven fuera de una panadería iniciada.
        tenancy()->end();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path($this->archivo));

        parent::tearDown();
    }

    public function test_en_el_navegador_la_pantalla_no_existe(): void
    {
        config(['nativephp-internal.running' => false]);

        $this->get('/interno/escritorio')->assertNotFound();
        $this->post('/interno/escritorio', ['codigo' => 'luna'])->assertNotFound();
    }

    public function test_la_pantalla_pide_el_codigo(): void
    {
        $this->get('/interno/escritorio')
            ->assertOk()
            ->assertSee('Ingresá el código de tu panadería')
            ->assertSee('name="codigo"', false);
    }

    public function test_guarda_el_codigo_y_salta_al_servidor(): void
    {
        $this->post('/interno/escritorio', ['codigo' => ' Testing '])
            ->assertRedirect('https://obrador.incgroupbo.com/testing');

        $this->assertSame('testing', trim((string) file_get_contents(storage_path($this->archivo))));
    }

    public function test_no_acepta_codigos_reservados_ni_invalidos(): void
    {
        $this->post('/interno/escritorio', ['codigo' => 'panel'])
            ->assertSessionHasErrors('codigo');

        $this->post('/interno/escritorio', ['codigo' => 'Luna!'])
            ->assertSessionHasErrors('codigo');

        $this->assertFileDoesNotExist(storage_path($this->archivo));
    }

    public function test_se_puede_cambiar_de_panaderia(): void
    {
        file_put_contents(storage_path($this->archivo), 'luna');

        $this->post('/interno/escritorio/olvidar')->assertRedirect(route('escritorio.mostrar'));

        $this->assertFileDoesNotExist(storage_path($this->archivo));
    }
}
