<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_and_login_page_render(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Parish Youth Council')
            ->assertSee('Join the PYC')
            ->assertSee('Become a Member');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome Back!')
            ->assertSee('Sign in to your account');
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
