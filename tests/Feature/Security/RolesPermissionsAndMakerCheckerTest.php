<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\Security\Exceptions\MakerCheckerViolationException;
use App\Support\Security\MakerCheckerGuard;
use App\Support\Security\PycPermission;
use App\Support\Security\PycRole;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesPermissionsAndMakerCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_all_seven_roles_and_forty_four_permissions_are_seeded(): void
    {
        $this->assertSame(7, Role::query()->count());
        $this->assertSame(44, count(PycPermission::cases()));
        $this->assertSame(44, Permission::query()->count());
    }

    public function test_super_administrator_and_auditor_cannot_bypass_financial_controls(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(PycRole::SuperAdministrator->value);

        $auditor = User::factory()->create();
        $auditor->assignRole(PycRole::Auditor->value);

        $this->assertTrue($superAdmin->can(PycPermission::UsersManage->value));
        $this->assertTrue($superAdmin->can(PycPermission::SettingsManage->value));
        $this->assertFalse($superAdmin->can(PycPermission::PaymentsApprove->value));
        $this->assertFalse($superAdmin->can(PycPermission::AccountingPost->value));

        $this->assertTrue($auditor->can(PycPermission::AccountingView->value));
        $this->assertTrue($auditor->can(PycPermission::AuditLogsView->value));
        $this->assertFalse($auditor->can(PycPermission::PaymentsRecordCash->value));
        $this->assertFalse($auditor->can(PycPermission::PaymentsApprove->value));
        $this->assertFalse($auditor->can(PycPermission::ExpensesApprove->value));
    }

    public function test_maker_checker_guard_blocks_self_approval_and_allows_independent_officer_approval(): void
    {
        $guard = app(MakerCheckerGuard::class);

        $treasurer = User::factory()->create();
        $treasurer->assignRole(PycRole::Treasurer->value);

        $president = User::factory()->create();
        $president->assignRole(PycRole::President->value);

        try {
            $guard->assertCanApprove(
                approver: $treasurer,
                initiatorUserId: (int) $treasurer->id,
                requiredPermission: PycPermission::PaymentsApprove,
                contextLabel: 'cash payment',
            );
            $this->fail('Expected MakerCheckerViolationException on self-approval.');
        } catch (MakerCheckerViolationException) {
            $this->assertDatabaseHas('audit_logs', [
                'event' => 'security.maker_checker_blocked',
                'actor_id' => $treasurer->id,
            ]);
        }

        // Independent approval by PYC President succeeds cleanly
        $guard->assertCanApprove(
            approver: $president,
            initiatorUserId: (int) $treasurer->id,
            requiredPermission: PycPermission::PaymentsApprove,
            contextLabel: 'cash payment',
        );

        $this->assertTrue(true);
    }
}
