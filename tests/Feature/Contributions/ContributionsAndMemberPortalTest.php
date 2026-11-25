<?php

declare(strict_types=1);

namespace Tests\Feature\Contributions;

use App\Models\User;
use App\Modules\Contributions\Contracts\ObligationAllocationServiceInterface;
use App\Modules\Contributions\Enums\ObligationStatus;
use App\Modules\Contributions\Models\ContributionCampaign;
use App\Modules\Contributions\Models\ContributionType;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Contributions\Services\ContributionCampaignService;
use App\Modules\Contributions\Services\MonthlyDuesService;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Support\Security\PycRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies Sprint 3 Member Portal & Sprint 4 Contributions/Dues acceptance criteria (Spec Section 6, 13.3 & 17.2).
 */
class ContributionsAndMemberPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_monthly_dues_generate_1000_pesewa_obligations_for_eligible_members_and_reruns_are_idempotent(): void
    {
        $activeMember = Member::query()->create([
            'member_number' => 'PYC-2026-0001',
            'first_name' => 'Ama',
            'last_name' => ' Boateng',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-03-10',
        ]);

        // Pending member must NOT be billed
        Member::query()->create([
            'first_name' => 'Unapproved',
            'last_name' => 'Applicant',
            'status' => MemberStatus::Pending->value,
        ]);

        // Member joining next month (Dec 2026) must NOT be billed for Nov 2026
        Member::query()->create([
            'member_number' => 'PYC-2026-0002',
            'first_name' => 'Future',
            'last_name' => 'Joiner',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-12-05',
        ]);

        $treasurer = User::factory()->withConfirmedTwoFactor()->create();
        $treasurer->assignRole(PycRole::Treasurer->value);

        $firstRun = app(MonthlyDuesService::class)->generate('2026-11', $treasurer);

        $this->assertSame(1000, $firstRun['amount_pesewas']);
        $this->assertSame(1, $firstRun['generated_count']);
        $this->assertSame(0, $firstRun['skipped_count']);

        $obligation = MemberObligation::query()->where('member_id', $activeMember->id)->firstOrFail();
        $this->assertSame(1000, $obligation->amount_due_pesewas);
        $this->assertSame('2026-11', $obligation->period_key);
        $this->assertSame(ObligationStatus::Unpaid->value, $obligation->status);

        // Repeated CLI generation does NOT duplicate obligations
        $this->artisan('pyc:generate-monthly-dues', ['period' => '2026-11'])
            ->assertSuccessful();

        $this->assertSame(1, MemberObligation::query()->count());
    }

    public function test_effective_dated_dues_changes_preserve_historical_obligations(): void
    {
        $member = Member::query()->create([
            'member_number' => 'PYC-2026-0005',
            'first_name' => 'Kofi',
            'last_name' => 'Mensah',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-01-15',
        ]);

        $treasurer = User::factory()->withConfirmedTwoFactor()->create();
        $treasurer->assignRole(PycRole::Treasurer->value);

        $duesService = app(MonthlyDuesService::class);
        $duesService->generate('2026-11', $treasurer);

        // Close 2026 GHS 10.00 campaign on 2026-12-31 and introduce GHS 15.00 (1,500p) from 2027-01-01
        $campaign2026 = ContributionCampaign::query()->where('code', 'monthly-youth-dues')->firstOrFail();
        $campaign2026->update(['effective_to' => '2026-12-31']);

        $duesType = ContributionType::query()->where('code', 'monthly_dues')->firstOrFail();
        app(ContributionCampaignService::class)->createCampaign([
            'contribution_type_id' => $duesType->id,
            'code' => 'monthly-youth-dues-2027',
            'title' => 'Monthly Youth Dues (2027 Rate)',
            'amount_pesewas' => 1500,
            'frequency' => 'monthly',
            'effective_from' => '2027-01-01',
        ], $treasurer);

        $duesService->generate('2027-01', $treasurer);

        $nov2026 = MemberObligation::query()->where('member_id', $member->id)->where('period_key', '2026-11')->firstOrFail();
        $jan2027 = MemberObligation::query()->where('member_id', $member->id)->where('period_key', '2027-01')->firstOrFail();

        $this->assertSame(1000, $nov2026->amount_due_pesewas);
        $this->assertSame(1500, $jan2027->amount_due_pesewas);
    }

    public function test_partial_and_advance_payments_allocate_across_fifo_and_future_months_accurately(): void
    {
        $member = Member::query()->create([
            'member_number' => 'PYC-2026-0008',
            'first_name' => 'Adwoa',
            'last_name' => 'Ofori',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-09-01',
        ]);

        $duesService = app(MonthlyDuesService::class);
        $duesService->generate('2026-10');
        $duesService->generate('2026-11');

        $allocator = app(ObligationAllocationServiceInterface::class);

        // 1) Partial payment of GHS 4.00 (400p) allocates to oldest unpaid month (2026-10)
        $partialSlices = $allocator->allocate($member->id, 400);
        $this->assertCount(1, $partialSlices);
        $this->assertSame('2026-10', $partialSlices[0]['period_key']);
        $this->assertSame(400, $partialSlices[0]['amount_pesewas']);

        $oct = MemberObligation::query()->where('member_id', $member->id)->where('period_key', '2026-10')->firstOrFail();
        $this->assertSame(ObligationStatus::PartiallyPaid->value, $oct->status);
        $this->assertSame(600, $oct->outstandingPesewas());

        // 2) Advance payment of GHS 26.00 (2,600p) clears remaining 600p on 2026-10, 1,000p on 2026-11, and auto-generates 2026-12 (1,000p)!
        $advanceSlices = $allocator->allocate($member->id, 2600);
        $this->assertCount(3, $advanceSlices);
        $this->assertSame(['2026-10', '2026-11', '2026-12'], array_column($advanceSlices, 'period_key'));
        $this->assertSame([600, 1000, 1000], array_column($advanceSlices, 'amount_pesewas'));

        $this->assertSame(0, $duesService->calculateOutstanding($member)->pesewas);
        $this->assertDatabaseHas('member_obligations', [
            'member_id' => $member->id,
            'period_key' => '2026-12',
            'status' => ObligationStatus::Paid->value,
            'amount_allocated_pesewas' => 1000,
        ]);
    }

    public function test_authorized_waivers_and_exemptions_update_balances_and_block_auditor(): void
    {
        $member = Member::query()->create([
            'member_number' => 'PYC-2026-0012',
            'first_name' => 'Yaw',
            'last_name' => 'Appiah',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-01-01',
        ]);

        app(MonthlyDuesService::class)->generate('2026-10');
        app(MonthlyDuesService::class)->generate('2026-11');

        $oct = MemberObligation::query()->where('member_id', $member->id)->where('period_key', '2026-10')->firstOrFail();
        $nov = MemberObligation::query()->where('member_id', $member->id)->where('period_key', '2026-11')->firstOrFail();

        $treasurer = User::factory()->withConfirmedTwoFactor()->create();
        $treasurer->assignRole(PycRole::Treasurer->value);

        $president = User::factory()->withConfirmedTwoFactor()->create();
        $president->assignRole(PycRole::President->value);

        $auditor = User::factory()->withConfirmedTwoFactor()->create();
        $auditor->assignRole(PycRole::Auditor->value);

        // Auditor is strictly read-only
        $this->actingAs($auditor)
            ->postJson("/api/v1/dues/{$oct->id}/adjustments", [
                'adjustment_type' => 'waiver',
                'amount_pesewas' => 500,
                'reason' => 'Unauthorized attempt',
            ])
            ->assertForbidden();

        // Treasurer applies 500p partial waiver on 2026-10
        $this->actingAs($treasurer)
            ->postJson("/api/v1/dues/{$oct->id}/adjustments", [
                'adjustment_type' => 'waiver',
                'amount_pesewas' => 500,
                'reason' => 'Approved student hardship partial relief',
            ])
            ->assertCreated();

        $this->assertSame(500, $oct->fresh()?->outstandingPesewas());

        // President grants 100% exemption on 2026-11
        $this->actingAs($president)
            ->postJson("/api/v1/dues/{$nov->id}/adjustments", [
                'adjustment_type' => 'exemption',
                'reason' => 'Approved full medical exemption',
            ])
            ->assertCreated();

        $nov->refresh();
        $this->assertSame(ObligationStatus::Exempt->value, $nov->status);
        $this->assertSame(0, $nov->outstandingPesewas());
        $this->assertSame(500, app(MonthlyDuesService::class)->calculateOutstanding($member)->pesewas);
    }

    public function test_member_portal_self_service_profile_and_strict_dues_isolation(): void
    {
        $memberOne = Member::query()->create([
            'member_number' => 'PYC-2026-0021',
            'first_name' => 'Akosua',
            'last_name' => 'Agyeman',
            'phone' => '+233241110000',
            'email' => 'akosua@example.org',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-01-01',
        ]);

        $memberTwo = Member::query()->create([
            'member_number' => 'PYC-2026-0022',
            'first_name' => 'Kwabena',
            'last_name' => 'Tetteh',
            'email' => 'kwabena@example.org',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-01-01',
        ]);

        app(MonthlyDuesService::class)->generate('2026-11');

        $userOne = User::factory()->create(['email' => 'akosua@example.org']);
        $userOne->assignRole(PycRole::Member->value);
        $memberOne->account()->create([
            'user_id' => $userOne->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Member views own profile & updates contact details (phone normalized to +233)
        $this->actingAs($userOne)
            ->get(route('member.profile'))
            ->assertOk()
            ->assertSee('PYC-2026-0021');

        $this->actingAs($userOne)
            ->patch(route('member.profile.update'), [
                'phone' => '055 987 6543',
                'address' => 'House 14, St. Cecilia Parish Lane',
                'occupation' => 'Software Engineer',
                'emergency_contact_name' => 'Grace Agyeman',
                'emergency_contact_phone' => '0244445555',
            ])
            ->assertRedirect(route('member.profile'));

        $this->assertSame('+233559876543', $memberOne->fresh()?->phone);

        // Member views own dues statement (web & API)
        $this->actingAs($userOne)
            ->get(route('member.dues'))
            ->assertOk()
            ->assertSee('GHS 10.00');

        $this->actingAs($userOne)
            ->getJson("/api/v1/dues/member/{$memberOne->id}")
            ->assertOk()
            ->assertJsonPath('data.total_outstanding.amount_pesewas', 1000);

        // Member is strictly blocked from viewing another member's dues or global arrears
        $this->actingAs($userOne)->getJson("/api/v1/dues/member/{$memberTwo->id}")->assertForbidden();
        $this->actingAs($userOne)->getJson('/api/v1/dues/outstanding')->assertForbidden();
        $this->actingAs($userOne)->get(route('contributions.index'))->assertForbidden();
    }
}
