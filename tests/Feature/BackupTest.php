<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    private User $administrator;

    private string $testDatabasePath;

    private array $existingSafetyBackups = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->existingSafetyBackups = glob(storage_path('app/backups/pre-restauracion-*.zip')) ?: [];
        $this->testDatabasePath = storage_path('framework/testing-backup.sqlite');
        File::ensureDirectoryExists(dirname($this->testDatabasePath));
        File::put($this->testDatabasePath, '');
        config(['database.connections.sqlite.database' => $this->testDatabasePath]);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->iniciarPanaderiaDePrueba();

        app(RoleSeeder::class)->run();
        $this->administrator = User::factory()->create();
        $this->administrator->assignRole('Administrador');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::delete($this->testDatabasePath);

        $currentSafetyBackups = glob(storage_path('app/backups/pre-restauracion-*.zip')) ?: [];
        File::delete(array_diff($currentSafetyBackups, $this->existingSafetyBackups));

        parent::tearDown();
    }

    public function test_an_administrator_can_download_a_complete_backup(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('El entorno de pruebas no dispone de ZipArchive.');
        }

        $response = $this->actingAs($this->administrator)
            ->get(route('system.backups.download'));

        $response->assertOk();
        $response->assertDownload();

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $this->assertNotFalse($zip->locateName('database/database.sqlite'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $zip->close();
        File::delete($path);
    }

    public function test_an_invalid_backup_is_rejected_without_replacing_data(): void
    {
        $response = $this->actingAs($this->administrator)
            ->from(route('system.backups.index'))
            ->post(route('system.backups.restore'), [
                'backup' => UploadedFile::fake()->createWithContent('invalido.zip', 'no es un zip'),
                'confirmation' => '1',
            ]);

        $response->assertRedirect(route('system.backups.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->administrator->id]);
    }

    public function test_a_valid_backup_can_restore_the_database(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('El entorno de pruebas no dispone de ZipArchive.');
        }

        $download = $this->actingAs($this->administrator)
            ->get(route('system.backups.download'));
        $backupPath = $download->baseResponse->getFile()->getPathname();
        $originalName = $this->administrator->name;

        $this->administrator->update(['name' => 'Nombre modificado']);

        $response = $this->actingAs($this->administrator)
            ->post(route('system.backups.restore'), [
                'backup' => new UploadedFile(
                    $backupPath,
                    'respaldo.zip',
                    'application/zip',
                    UPLOAD_ERR_OK,
                    true
                ),
                'confirmation' => '1',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertSame(
            $originalName,
            User::query()->findOrFail($this->administrator->id)->name
        );

        File::delete($backupPath);
    }

    /**
     * El caso del traspaso: un respaldo de la aplicación de escritorio de una
     * versión anterior (le falta la última migración) se pone al día y se
     * importa, con sus propios usuarios.
     */
    public function test_an_older_desktop_backup_is_upgraded_and_imported(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('El entorno de pruebas no dispone de ZipArchive.');
        }

        $antigua = storage_path('framework/testing-escritorio.sqlite');
        $zipPath = storage_path('framework/testing-escritorio.zip');
        File::ensureDirectoryExists(dirname($antigua));
        File::put($antigua, '');
        config(['database.connections.escritorio' => [
            'driver' => 'sqlite',
            'database' => $antigua,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        Artisan::call('migrate', [
            '--database' => 'escritorio',
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);
        // Le falta la última migración, como a un escritorio sin actualizar.
        $ultima = DB::connection('escritorio')->table('migrations')->orderByDesc('id')->value('migration');
        Artisan::call('migrate:rollback', [
            '--database' => 'escritorio',
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--step' => 1,
            '--force' => true,
        ]);
        $this->assertFalse(DB::connection('escritorio')->table('migrations')->where('migration', $ultima)->exists());

        DB::connection('escritorio')->table('users')->insert([
            'id' => 7,
            'name' => 'Dueña del escritorio',
            'email' => 'duena@escritorio.test',
            'password' => bcrypt('ClaveSegura2026'),
        ]);
        DB::connection('escritorio')->table('categorias')->insert(['nombre' => 'Traída del escritorio']);
        DB::purge('escritorio');

        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($antigua, 'database/database.sqlite');
        $zip->addFromString('manifest.json', '{"version":"1.0.14","format":1}');
        $zip->close();

        $this->actingAs($this->administrator)
            ->post(route('system.backups.restore'), [
                'backup' => new UploadedFile($zipPath, 'escritorio.zip', 'application/zip', UPLOAD_ERR_OK, true),
                'confirmation' => '1',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['id' => 7, 'email' => 'duena@escritorio.test']);
        $this->assertDatabaseMissing('users', ['id' => $this->administrator->id]);
        $this->assertDatabaseHas('categorias', ['nombre' => 'Traída del escritorio']);

        File::delete([$antigua, $zipPath]);
    }
}
