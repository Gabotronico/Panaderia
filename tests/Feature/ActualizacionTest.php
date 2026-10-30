<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Actualizaciones\Descarga;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Events\AutoUpdater\DownloadProgress;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;
use Tests\TestCase;

/**
 * Actualizador de la app de escritorio: última versión publicada en GitHub,
 * botón del encabezado y guardias para que descargar e instalar solo existan
 * dentro de la app empaquetada.
 */
class ActualizacionTest extends TestCase
{
    use RefreshDatabase;

    private const URL_RELEASE = 'https://api.github.com/repos/dueno/releases/releases/latest';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'actualizaciones.owner' => 'dueno',
            'actualizaciones.repo' => 'releases',
            'nativephp-internal.running' => false,
            'nativephp.version' => '1.0.15',
        ]);
    }

    /** @return array<string, mixed> */
    private function release(string $version = '1.0.16'): array
    {
        return [
            'tag_name' => 'v'.$version,
            'body' => "Arreglos de caja\r\nMejoras en planillas",
            'published_at' => '2026-11-02T18:00:00Z',
            'html_url' => 'https://github.com/dueno/releases/releases/tag/v'.$version,
            'assets' => [
                [
                    'name' => "Obrador-Setup-{$version}.exe.blockmap",
                    'browser_download_url' => "https://example.test/Obrador-Setup-{$version}.exe.blockmap",
                    'size' => 1200,
                ],
                [
                    'name' => "Obrador-Setup-{$version}.exe",
                    'browser_download_url' => "https://example.test/Obrador-Setup-{$version}.exe",
                    'size' => 90_000_000,
                ],
            ],
        ];
    }

    private function administrador(): User
    {
        app(RoleSeeder::class)->run();

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@prueba.test',
            'password' => 'Password123',
        ]);
        $admin->assignRole('Administrador');

        return $admin;
    }

    public function test_estado_ofrece_la_ultima_version_publicada(): void
    {
        Http::fake([self::URL_RELEASE => Http::response($this->release(), 200)]);

        $this->actingAs($this->administrador())
            ->getJson('/prueba/sistema/actualizaciones')
            ->assertOk()
            ->assertJsonPath('escritorio', false)
            ->assertJsonPath('instalada', '1.0.15')
            ->assertJsonPath('hay_actualizacion', true)
            ->assertJsonPath('ultima.version', '1.0.16')
            ->assertJsonPath('ultima.url', 'https://example.test/Obrador-Setup-1.0.16.exe')
            ->assertJsonPath('descarga.estado', 'inactiva');
    }

    public function test_si_la_version_publicada_es_la_misma_no_hay_aviso(): void
    {
        Http::fake([self::URL_RELEASE => Http::response($this->release('1.0.15'), 200)]);

        $this->actingAs($this->administrador())
            ->getJson('/prueba/sistema/actualizaciones')
            ->assertOk()
            ->assertJsonPath('hay_actualizacion', false)
            ->assertJsonPath('ultima.version', '1.0.15');
    }

    public function test_sin_release_publicado_no_hay_aviso(): void
    {
        Http::fake([self::URL_RELEASE => Http::response(['message' => 'Not Found'], 404)]);

        $this->actingAs($this->administrador())
            ->getJson('/prueba/sistema/actualizaciones')
            ->assertOk()
            ->assertJsonPath('ultima', null)
            ->assertJsonPath('hay_actualizacion', false);
    }

    public function test_en_el_navegador_no_se_puede_descargar_ni_instalar(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)->postJson('/prueba/sistema/actualizaciones/descargar')->assertNotFound();
        $this->actingAs($admin)->getJson('/prueba/sistema/actualizaciones/progreso')->assertNotFound();
        $this->actingAs($admin)->postJson('/prueba/sistema/actualizaciones/instalar')->assertNotFound();
    }

    public function test_el_encabezado_incluye_el_actualizador(): void
    {
        $this->actingAs($this->administrador())
            ->get('/prueba/sistema/acerca-de')
            ->assertOk()
            ->assertSee('id="actualizador"', false)
            ->assertSee('id="actualizador-boton"', false)
            ->assertSee('data-escritorio="0"', false);
    }

    public function test_la_descarga_guarda_el_progreso_para_la_pantalla(): void
    {
        Descarga::preparar('1.0.16');
        $this->assertSame('descargando', Descarga::estado()['estado']);
        $this->assertSame(0, Descarga::estado()['percent']);

        Descarga::progreso(new DownloadProgress(
            total: 100_000_000,
            delta: 1_000_000,
            transferred: 42_000_000,
            percent: 42.0,
            bytesPerSecond: 3_500_000,
        ));

        $estado = Descarga::estado();
        $this->assertSame(42.0, $estado['percent']);
        $this->assertSame(42_000_000, $estado['transferred']);

        Descarga::lista(new UpdateDownloaded(
            downloadedFile: 'Obrador-Setup-1.0.16.exe',
            version: '1.0.16',
            files: [],
            releaseDate: '2026-11-02T18:00:00Z',
        ));

        $this->assertSame('lista', Descarga::estado()['estado']);
        $this->assertSame('1.0.16', Descarga::estado()['version']);
    }
}
