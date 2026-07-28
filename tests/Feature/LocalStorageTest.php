<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalStorageTest extends TestCase
{
    use RefreshDatabase;

    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testFile = storage_path('app/public/testing/local-storage.txt');
        File::ensureDirectoryExists(dirname($this->testFile));
        File::put($this->testFile, 'contenido local');
    }

    protected function tearDown(): void
    {
        File::delete($this->testFile);
        File::deleteDirectory(dirname($this->testFile));

        parent::tearDown();
    }

    public function test_an_authenticated_user_can_read_a_public_local_file(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('local-storage.show', ['path' => 'testing/local-storage.txt']));

        $response
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame(
            realpath($this->testFile),
            realpath($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_a_guest_cannot_read_a_public_local_file(): void
    {
        User::factory()->create();

        $this->get(route('local-storage.show', ['path' => 'testing/local-storage.txt']))
            ->assertRedirect(route('login'));
    }
}
