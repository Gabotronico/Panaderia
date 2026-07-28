<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DesktopReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_expired_session_returns_to_setup_during_first_run(): void
    {
        Route::middleware('web')->post('/testing/expired-session', fn () => abort(419))
            ->name('setup.testing-expired');

        $response = $this->post('/testing/expired-session');

        $response->assertRedirect(route('setup.create'));
        $response->assertSessionHas('error');
    }

    public function test_an_expired_session_returns_to_login_after_setup(): void
    {
        User::factory()->create();
        Route::middleware('web')->post('/testing/expired-session', fn () => abort(419));

        $response = $this->post('/testing/expired-session');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_html_pages_are_not_cached_by_the_desktop_webview(): void
    {
        $response = $this->get('/setup');

        $response->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
    }
}
