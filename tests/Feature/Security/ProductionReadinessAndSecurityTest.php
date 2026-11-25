<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\Security\PycRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies Sprint 8 Security & Operational Readiness criteria (Spec Section 4.4, 15.5 & 17.4).
 */
class ProductionReadinessAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_defensive_security_headers_are_applied_to_web_and_api_responses(): void
    {
        $webResp = $this->get('/login');
        $webResp->assertOk();
        $webResp->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $webResp->assertHeader('X-Content-Type-Options', 'nosniff');
        $webResp->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $webResp->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $apiResp = $this->getJson('/api/v1/status');
        $apiResp->assertOk();
        $apiResp->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_login_rate_limiter_throttles_brute_force_attempts(): void
    {
        User::factory()->create([
            'email' => 'officer@stceciliapyc.org',
            'password' => 'CorrectStrongPass123',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/login', [
                'email' => 'officer@stceciliapyc.org',
                'password' => 'WrongPassword999',
            ]);
        }

        // 6th attempt within a minute triggers 429 Too Many Requests
        $this->postJson('/login', [
            'email' => 'officer@stceciliapyc.org',
            'password' => 'WrongPassword999',
        ])->assertStatus(429);
    }

    public function test_operational_health_cli_and_permission_gated_health_endpoint(): void
    {
        $this->artisan('pyc:health-check')->assertSuccessful();

        $member = User::factory()->create();
        $member->assignRole(PycRole::Member->value);

        $auditor = User::factory()->withConfirmedTwoFactor()->create();
        $auditor->assignRole(PycRole::Auditor->value);

        $this->actingAs($member)->getJson('/api/v1/system/health')->assertForbidden();

        $this->actingAs($auditor)
            ->getJson('/api/v1/system/health')
            ->assertOk()
            ->assertJsonPath('data.healthy', true)
            ->assertJsonPath('data.database.connected', true);
    }
}
