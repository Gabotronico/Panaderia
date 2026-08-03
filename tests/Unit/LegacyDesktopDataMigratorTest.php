<?php

namespace Tests\Unit;

use App\Support\LegacyDesktopDataMigrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LegacyDesktopDataMigratorTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = dirname(__DIR__, 2).'/storage/framework/testing/legacy-desktop-'.uniqid();
        (new Filesystem)->mkdir($this->basePath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->remove($this->basePath);

        parent::tearDown();
    }

    public function test_it_moves_the_legacy_database_and_public_storage_to_obrador(): void
    {
        $legacy = $this->basePath.'/panaderia-escritorio';
        $current = $this->basePath.'/obrador';
        $database = $current.'/database/database.sqlite';
        $storage = $current.'/storage';

        (new Filesystem)->mkdir([$legacy.'/database', $legacy.'/storage/app/public/productos', dirname($database)]);
        file_put_contents($legacy.'/database/database.sqlite', 'legacy-sqlite-data');
        file_put_contents($legacy.'/storage/app/public/productos/pan.jpg', 'image-data');
        file_put_contents($database, '');

        $this->assertTrue(LegacyDesktopDataMigrator::migratePaths(
            $legacy,
            $current,
            $database,
            $storage,
        ));

        $this->assertSame('legacy-sqlite-data', file_get_contents($database));
        $this->assertSame('image-data', file_get_contents($storage.'/app/public/productos/pan.jpg'));
        $this->assertFileExists($current.'/.migrated-from-panaderia-escritorio');
    }

    public function test_it_never_overwrites_an_existing_obrador_database(): void
    {
        $legacy = $this->basePath.'/panaderia-escritorio';
        $current = $this->basePath.'/obrador';
        $database = $current.'/database/database.sqlite';

        (new Filesystem)->mkdir([$legacy.'/database', dirname($database)]);
        file_put_contents($legacy.'/database/database.sqlite', 'legacy-sqlite-data');
        file_put_contents($database, 'existing-obrador-data');

        $this->assertFalse(LegacyDesktopDataMigrator::migratePaths(
            $legacy,
            $current,
            $database,
            $current.'/storage',
        ));

        $this->assertSame('existing-obrador-data', file_get_contents($database));
    }
}