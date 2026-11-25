<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\Security\PycRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationAndPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_self_registration_route_is_disabled(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->get('/register')->assertNotFound();
    }

    public function test_executive_officer_without_confirmed_2fa_is_redirected_to_security_setup(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(PycRole::SuperAdministrator->value);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('profile.security'));

        $this->actingAs($admin)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee('Two-Factor Authentication');
    }

    public function test_executive_officer_with_confirmed_2fa_accesses_executive_portal_and_admin_views(): void
    {
        $admin = User::factory()->withConfirmedTwoFactor()->create();
        $admin->assignRole(PycRole::SuperAdministrator->value);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Executive & Administrative Overview');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk();
    }

    public function test_member_accesses_only_member_portal_and_is_forbidden_from_admin_routes(): void
    {
        $member = User::factory()->create();
        $member->assignRole(PycRole::Member->value);

        $this->actingAs($member)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Personal Member Portal');

        $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.settings.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($member)->get(route('modules.overview', 'accounting'))->assertForbidden();
    }

    public function test_suspended_user_cannot_authenticate(): void
    {
        $suspended = User::factory()->create([
            'email' => 'suspended@stceciliapyc.org',
            'password' => 'StrongPass123',
            'status' => 'suspended',
        ]);
        $suspended->assignRole(PycRole::Member->value);

        $response = $this->from('/login')->post('/login', [
            'email' => 'suspended@stceciliapyc.org',
            'password' => 'StrongPass123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_one_time_admin_provisioning_command_succeeds_once_and_blocks_duplicate_run(): void
    {
        $this->artisan('pyc:provision-admin', [
            '--name' => 'Initial PYC Admin',
            '--email' => 'bootstrap@stceciliapyc.org',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'bootstrap@stceciliapyc.org',
            'must_change_password' => true,
        ]);

        $this->artisan('pyc:provision-admin', [
            '--email' => 'second@stceciliapyc.org',
        ])->assertFailed();
    }
}
