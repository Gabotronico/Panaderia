<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fresh_installation_redirects_to_the_setup_wizard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('setup.create'));
    }
}
