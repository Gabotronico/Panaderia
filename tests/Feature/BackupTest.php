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

        $this->existingSafetyBackups = glob(storage_path('app/backups/pre-restauracion-*.sqlite')) ?: [];
        $this->testDatabasePath = storage_path('framework/testing-backup.sqlite');
        File::ensureDirectoryExists(dirname($this->testDatabasePath));
        File::put($this->testDatabasePath, '');
        config(['database.connections.sqlite.database' => $this->testDatabasePath]);
        DB::purge('sqlite');
        Artisan::call('migrate:fresh', ['--force' => true]);

        app(RoleSeeder::class)->run();
        $this->administrator = User::factory()->create();
        $this->administrator->assignRole('Administrador');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::delete($this->testDatabasePath);

        $currentSafetyBackups = glob(storage_path('app/backups/pre-restauracion-*.sqlite')) ?: [];
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
}
