<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_root_redirects_to_login_and_login_page_renders(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Parish Youth Council Portal');
    }

    public function test_api_v1_status_endpoint_returns_consistent_json(): void
    {
        $this->getJson('/api/v1/status')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'currency' => 'GHS',
                    'timezone' => 'Africa/Accra',
                ],
            ]);
    }
}
