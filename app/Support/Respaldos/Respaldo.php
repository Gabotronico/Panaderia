<?php

namespace App\Support\Respaldos;

use App\Models\AppSetting;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use ZipArchive;

/**
 * Respaldo y restauración de los datos de una panadería.
 *
 * El ZIP es el mismo que generaba la aplicación de escritorio: la base en
 * database/database.sqlite, las imágenes en storage/public y un manifest.
 * Así un respaldo de escritorio se restaura en la web y viceversa. En la web
 * los datos viven en MySQL, por eso exportar e importar pasan por un SQLite
 * temporal en lugar de copiar el archivo de la base.
 *
 * Trabaja sobre la conexión y el storage activos: con tenancy iniciada, los
 * de la panadería.
 */
final class Respaldo
{
    public const FORMATO = 2;

    // Una conexión para cada sentido: restaurar exporta la copia previa
    // mientras tiene abierto el respaldo que va a importar.
    private const CONEXION_EXPORTACION = 'respaldo_exportacion';

    private const CONEXION_IMPORTACION = 'respaldo_importacion';

    private const MAXIMO_DESCOMPRIMIDO = 1024 * 1024 * 1024;

    /** Copias previas a una restauración que se conservan por panadería. */
    private const COPIAS_PREVIAS = 5;

    /** Genera el ZIP del respaldo y devuelve su ruta. */
    public static function exportar(): string
    {
        $directorio = self::directorio();
        $sqlite = $directorio.DIRECTORY_SEPARATOR.'exportacion-'.Str::uuid().'.sqlite';
        $zipPath = $directorio.DIRECTORY_SEPARATOR.Str::slug(config('app.name'), '_').'_'.now()->format('Y-m-d_H-i-s').'.zip';

        File::put($sqlite, '');

        try {
            $copia = self::conectar(self::CONEXION_EXPORTACION, $sqlite);
            self::migrar(self::CONEXION_EXPORTACION);
            // Las migraciones de datos (permisos, roles) dejan filas en la
            // base nueva; el copiador las reemplaza con las reales.
            CopiadorDeTablas::copiar(DB::connection(), $copia);
            self::desconectar(self::CONEXION_EXPORTACION);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo de respaldo.');
            }

            $zip->addFile($sqlite, 'database/database.sqlite');
            self::agregarDirectorio($zip, storage_path('app/public'), 'storage/public');
            $zip->addFromString('manifest.json', json_encode([
                'application' => config('app.name'),
                'tenant' => tenant()?->getTenantKey(),
                'created_at' => now()->toIso8601String(),
                'format' => self::FORMATO,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // ZipArchive lee los archivos recién al cerrar.
            $zip->close();
        } finally {
            self::desconectar(self::CONEXION_EXPORTACION);
            File::delete($sqlite);
        }

        return $zipPath;
    }

    /**
     * Reemplaza los datos actuales con los del respaldo.
     *
     * Antes de tocar nada se guarda una copia de los datos actuales
     * (pre-restauracion-*.zip). El reemplazo de filas ocurre en una sola
     * transacción: si falla, la base queda como estaba.
     *
     * @return array<string, int> filas restauradas por tabla
     */
    public static function restaurar(string $zipPath): array
    {
        $temporal = self::directorio().DIRECTORY_SEPARATOR.'restauracion-'.Str::uuid();
        File::ensureDirectoryExists($temporal);

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('No se pudo abrir el respaldo.');
            }

            try {
                self::validarZip($zip);
                $zip->extractTo($temporal);
            } finally {
                $zip->close();
            }

            $sqlite = $temporal.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';
            self::validarSqlite($sqlite);

            self::guardarCopiaPrevia();

            // Un respaldo de una versión anterior (el escritorio 1.0.14, por
            // ejemplo) se pone al día sobre la copia extraída antes de
            // importarlo, con las mismas migraciones que usa la web.
            $origen = self::conectar(self::CONEXION_IMPORTACION, $sqlite);
            self::migrar(self::CONEXION_IMPORTACION);

            $copiadas = CopiadorDeTablas::copiar($origen, DB::connection());
            self::desconectar(self::CONEXION_IMPORTACION);

            // Los usuarios cambiaron: nadie sigue logueado con un id viejo.
            DB::table('sessions')->delete();

            $imagenes = $temporal.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'public';
            if (File::isDirectory($imagenes)) {
                File::ensureDirectoryExists(storage_path('app/public'));
                File::copyDirectory($imagenes, storage_path('app/public'));
            }

            Cache::flush();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            AppSetting::olvidarNombre();

            return $copiadas;
        } finally {
            self::desconectar(self::CONEXION_IMPORTACION);
            File::deleteDirectory($temporal);
        }
    }

    /** Tamaño aproximado de los datos de la base activa, en bytes. */
    public static function tamanoBase(): int
    {
        $conexion = DB::connection();

        return match ($conexion->getDriverName()) {
            'sqlite' => File::exists($conexion->getDatabaseName()) ? File::size($conexion->getDatabaseName()) : 0,
            'mysql', 'mariadb' => (int) $conexion->table('information_schema.tables')
                ->where('table_schema', $conexion->getDatabaseName())
                ->sum(DB::raw('data_length + index_length')),
            default => 0,
        };
    }

    private static function guardarCopiaPrevia(): void
    {
        $previa = self::exportar();
        $destino = self::directorio().DIRECTORY_SEPARATOR.'pre-restauracion-'.now()->format('Y-m-d_H-i-s').'.zip';

        if (! File::move($previa, $destino)) {
            throw new RuntimeException('No se pudo guardar la copia de seguridad previa a la restauración.');
        }

        $copias = glob(self::directorio().DIRECTORY_SEPARATOR.'pre-restauracion-*.zip') ?: [];
        rsort($copias);
        File::delete(\array_slice($copias, self::COPIAS_PREVIAS));
    }

    private static function conectar(string $nombre, string $sqlite): Connection
    {
        config(['database.connections.'.$nombre => [
            'driver' => 'sqlite',
            'database' => $sqlite,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge($nombre);

        return DB::connection($nombre);
    }

    private static function desconectar(string $nombre): void
    {
        DB::purge($nombre);
    }

    private static function migrar(string $conexion): void
    {
        $codigo = Artisan::call('migrate', [
            '--database' => $conexion,
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($codigo !== 0) {
            throw new RuntimeException('No se pudo actualizar la base del respaldo: '.trim(Artisan::output()));
        }
    }

    private static function directorio(): string
    {
        $directorio = storage_path('app/backups');
        File::ensureDirectoryExists($directorio);

        return $directorio;
    }

    private static function agregarDirectorio(ZipArchive $zip, string $directorio, string $prefijo): void
    {
        if (! File::isDirectory($directorio)) {
            return;
        }

        foreach (File::allFiles($directorio) as $archivo) {
            $relativa = str_replace('\\', '/', $archivo->getRelativePathname());
            $zip->addFile($archivo->getPathname(), $prefijo.'/'.$relativa);
        }
    }

    private static function validarZip(ZipArchive $zip): void
    {
        $tieneBase = false;
        $descomprimido = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            $datos = $zip->statIndex($i);
            $descomprimido += \is_array($datos) ? (int) ($datos['size'] ?? 0) : 0;

            if ($descomprimido > self::MAXIMO_DESCOMPRIMIDO) {
                throw new RuntimeException('El contenido descomprimido del respaldo supera 1 GB.');
            }

            if (
                str_starts_with($nombre, '/')
                || str_contains($nombre, '../')
                || preg_match('/^[A-Za-z]:\//', $nombre)
            ) {
                throw new RuntimeException('El respaldo contiene rutas no permitidas.');
            }

            $tieneBase = $tieneBase || $nombre === 'database/database.sqlite';
        }

        if (! $tieneBase) {
            throw new RuntimeException('El respaldo no contiene una base de datos válida.');
        }
    }

    private static function validarSqlite(string $ruta): void
    {
        if (! File::exists($ruta)) {
            throw new RuntimeException('No se encontró la base de datos dentro del respaldo.');
        }

        $archivo = fopen($ruta, 'rb');
        $cabecera = $archivo ? fread($archivo, 16) : false;
        if (\is_resource($archivo)) {
            fclose($archivo);
        }

        if ($cabecera !== "SQLite format 3\0") {
            throw new RuntimeException('La base de datos del respaldo está dañada o no es SQLite.');
        }
    }
}
