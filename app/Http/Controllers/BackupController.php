<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): View
    {
        $databasePath = $this->sqliteDatabasePath();

        return view('system.backups', [
            'databaseSize' => File::exists($databasePath) ? File::size($databasePath) : 0,
            'lastModified' => File::exists($databasePath)
                ? now()->setTimestamp(File::lastModified($databasePath))
                : null,
        ]);
    }

    public function download(): BinaryFileResponse|RedirectResponse
    {
        if (! class_exists(ZipArchive::class)) {
            return back()->with('error', 'El runtime no dispone de soporte ZIP para crear el respaldo.');
        }

        $databasePath = $this->sqliteDatabasePath();
        abort_unless(File::exists($databasePath), 500, 'No se encontró la base de datos local.');

        $backupDirectory = storage_path('app/backups');
        File::ensureDirectoryExists($backupDirectory);

        $filename = sprintf(
            '%s_%s.zip',
            Str::slug(config('app.name'), '_'),
            now()->format('Y-m-d_H-i-s')
        );
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;

        try {
            DB::statement('PRAGMA wal_checkpoint(FULL)');
        } catch (\Throwable) {
            // La base puede no estar usando WAL; el archivo sigue siendo respaldable.
        }

        $zip = new ZipArchive;
        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo de respaldo.');
        }

        $zip->addFile($databasePath, 'database/database.sqlite');
        $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage/public');
        $zip->addFromString('manifest.json', json_encode([
            'application' => config('app.name'),
            'version' => config('nativephp.version', '1.0.0'),
            'created_at' => now()->toIso8601String(),
            'format' => 1,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();

        return response()->download($backupPath, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup' => ['required', 'file', 'max:524288'],
            'confirmation' => ['accepted'],
        ], [
            'backup.required' => 'Seleccione un archivo de respaldo.',
            'backup.max' => 'El respaldo no puede superar 512 MB.',
            'confirmation.accepted' => 'Debe confirmar que desea reemplazar los datos actuales.',
        ]);

        if (strtolower($validated['backup']->getClientOriginalExtension()) !== 'zip') {
            return back()->with('error', 'El archivo seleccionado debe tener extensión .zip.');
        }

        if (! class_exists(ZipArchive::class)) {
            return back()->with('error', 'El runtime no dispone de soporte ZIP para restaurar respaldos.');
        }

        $temporaryDirectory = storage_path('app/backups/restore-'.Str::uuid());
        File::ensureDirectoryExists($temporaryDirectory);
        $databasePath = $this->sqliteDatabasePath();
        $safetyCopy = storage_path('app/backups/pre-restauracion-'.now()->format('Y-m-d_H-i-s').'.sqlite');
        $databaseWasReplaced = false;

        try {
            $zip = new ZipArchive;
            if ($zip->open($validated['backup']->getRealPath()) !== true) {
                throw new \RuntimeException('No se pudo abrir el respaldo.');
            }

            $this->assertSafeBackup($zip);
            $zip->extractTo($temporaryDirectory);
            $zip->close();

            $restoredDatabase = $temporaryDirectory.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';
            $this->assertValidSqliteDatabase($restoredDatabase);

            File::ensureDirectoryExists(dirname($safetyCopy));
            if (! File::copy($databasePath, $safetyCopy)) {
                throw new \RuntimeException('No se pudo crear la copia de seguridad previa a la restauración.');
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            DB::disconnect();

            if (! File::copy($restoredDatabase, $databasePath)) {
                File::copy($safetyCopy, $databasePath);
                throw new \RuntimeException('No se pudo reemplazar la base de datos local.');
            }
            $databaseWasReplaced = true;

            $restoredStorage = $temporaryDirectory.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'public';
            if (File::isDirectory($restoredStorage)) {
                File::ensureDirectoryExists(storage_path('app/public'));
                File::copyDirectory($restoredStorage, storage_path('app/public'));
            }

            DB::purge();
            DB::reconnect();
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('cache:clear');

            return redirect()->route('login')->with(
                'status',
                'Respaldo restaurado correctamente. Inicie sesión nuevamente.'
            );
        } catch (\Throwable $exception) {
            if ($databaseWasReplaced && File::exists($safetyCopy)) {
                DB::disconnect();
                File::copy($safetyCopy, $databasePath);
                DB::purge();
                DB::reconnect();
            }

            Log::error('No se pudo restaurar el respaldo local.', [
                'exception' => $exception,
            ]);

            return back()->with('error', 'No se pudo restaurar el respaldo: '.$exception->getMessage());
        } finally {
            if (File::isDirectory($temporaryDirectory)) {
                File::deleteDirectory($temporaryDirectory);
            }
        }
    }

    private function sqliteDatabasePath(): string
    {
        abort_unless(DB::getDriverName() === 'sqlite', 500, 'Los respaldos locales requieren SQLite.');

        return DB::connection()->getDatabaseName();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $prefix): void
    {
        if (! File::isDirectory($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relativePath = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($directory))), '/');
            $zip->addFile($file->getPathname(), $prefix.'/'.$relativePath);
        }
    }

    private function assertSafeBackup(ZipArchive $zip): void
    {
        $hasDatabase = false;
        $uncompressedSize = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($index));
            $statistics = $zip->statIndex($index);
            $uncompressedSize += is_array($statistics) ? (int) ($statistics['size'] ?? 0) : 0;

            if ($uncompressedSize > 1024 * 1024 * 1024) {
                $zip->close();
                throw new \RuntimeException('El contenido descomprimido del respaldo supera 1 GB.');
            }

            if ($name === 'database/database.sqlite') {
                $hasDatabase = true;
            }

            if (
                str_starts_with($name, '/')
                || str_contains($name, '../')
                || preg_match('/^[A-Za-z]:\//', $name)
            ) {
                $zip->close();
                throw new \RuntimeException('El respaldo contiene rutas no permitidas.');
            }
        }

        if (! $hasDatabase) {
            $zip->close();
            throw new \RuntimeException('El respaldo no contiene una base de datos válida.');
        }
    }

    private function assertValidSqliteDatabase(string $path): void
    {
        if (! File::exists($path)) {
            throw new \RuntimeException('No se encontró la base de datos dentro del respaldo.');
        }

        $handle = fopen($path, 'rb');
        $header = $handle ? fread($handle, 16) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }

        if ($header !== "SQLite format 3\0") {
            throw new \RuntimeException('La base de datos del respaldo está dañada o no es SQLite.');
        }
    }
}
