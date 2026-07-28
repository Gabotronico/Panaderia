<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_user_can_complete_the_setup_wizard(): void
    {
        $response = $this->post(route('setup.store'), [
            'business_name' => 'Panadería Central',
            'name' => 'Administradora',
            'email' => 'admin@example.test',
            'password' => 'ClaveSegura2026',
            'password_confirmation' => 'ClaveSegura2026',
        ]);

        $response->assertRedirect(route('home'));

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('Administrador'));
        $this->assertSame('Panadería Central', AppSetting::read('business_name'));
    }

    public function test_setup_cannot_be_executed_twice(): void
    {
        User::factory()->create();

        $response = $this->post(route('setup.store'), [
            'business_name' => 'Otro negocio',
            'name' => 'Otro administrador',
            'email' => 'otro@example.test',
            'password' => 'OtraClave2026',
            'password_confirmation' => 'OtraClave2026',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }
}
