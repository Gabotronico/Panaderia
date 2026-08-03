<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

final class LegacyDesktopDataMigrator
{
    private const LEGACY_DIRECTORY = 'panaderia-escritorio';

    public static function migrate(): void
    {
        if (! filter_var(getenv('NATIVEPHP_RUNNING') ?: false, FILTER_VALIDATE_BOOL)) {
            return;
        }

        $appData = getenv('NATIVEPHP_APP_DATA_PATH') ?: null;
        $currentUserData = getenv('NATIVEPHP_USER_DATA_PATH') ?: null;
        $currentDatabase = getenv('NATIVEPHP_DATABASE_PATH') ?: null;
        $currentStorage = getenv('NATIVEPHP_STORAGE_PATH') ?: null;

        if (! $appData || ! $currentUserData || ! $currentDatabase || ! $currentStorage) {
            return;
        }

        $legacyUserData = rtrim($appData, '\\/').DIRECTORY_SEPARATOR.self::LEGACY_DIRECTORY;

        self::migratePaths($legacyUserData, $currentUserData, $currentDatabase, $currentStorage);
    }

    public static function migratePaths(
        string $legacyUserData,
        string $currentUserData,
        string $currentDatabase,
        string $currentStorage,
    ): bool {
        if (self::normalize($legacyUserData) === self::normalize($currentUserData)) {
            return false;
        }

        $separator = DIRECTORY_SEPARATOR;
        $marker = rtrim($currentUserData, '\\/').$separator.'.migrated-from-'.self::LEGACY_DIRECTORY;
        $legacyDatabase = rtrim($legacyUserData, '\\/').$separator.'database'.$separator.'database.sqlite';

        if (is_file($marker) || ! is_file($legacyDatabase) || filesize($legacyDatabase) === 0) {
            return false;
        }

        if (is_file($currentDatabase) && filesize($currentDatabase) > 0) {
            return false;
        }

        $filesystem = new Filesystem;
        $temporaryDatabase = $currentDatabase.'.migrating';

        try {
            $legacyStorage = rtrim($legacyUserData, '\\/').$separator.'storage'.$separator.'app';
            $currentStorageApp = rtrim($currentStorage, '\\/').$separator.'app';

            if (is_dir($legacyStorage)) {
                $filesystem->mkdir($currentStorageApp);
                $filesystem->mirror($legacyStorage, $currentStorageApp, null, [
                    'override' => true,
                    'delete' => false,
                ]);
            }

            $filesystem->mkdir(dirname($currentDatabase));
            $filesystem->mkdir($currentUserData);
            $filesystem->copy($legacyDatabase, $temporaryDatabase, true);

            clearstatcache(true, $temporaryDatabase);
            if (! is_file($temporaryDatabase) || filesize($temporaryDatabase) !== filesize($legacyDatabase)) {
                throw new RuntimeException('La copia temporal de SQLite quedó incompleta.');
            }

            $filesystem->remove($currentDatabase);
            if (! @rename($temporaryDatabase, $currentDatabase)) {
                $filesystem->copy($temporaryDatabase, $currentDatabase, true);
                $filesystem->remove($temporaryDatabase);
            }

            foreach (['-wal', '-shm'] as $suffix) {
                if (is_file($legacyDatabase.$suffix)) {
                    $filesystem->copy($legacyDatabase.$suffix, $currentDatabase.$suffix, true);
                }
            }

            file_put_contents($marker, json_encode([
                'source' => $legacyUserData,
                'migrated_at' => date(DATE_ATOM),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $exception) {
            $filesystem->remove($temporaryDatabase);

            throw new RuntimeException(
                'No se pudieron migrar los datos de la instalación anterior a Obrador.',
                0,
                $exception,
            );
        }

        return true;
    }

    private static function normalize(string $path): string
    {
        return strtolower(str_replace('\\', '/', rtrim($path, '\\/')));
    }
}