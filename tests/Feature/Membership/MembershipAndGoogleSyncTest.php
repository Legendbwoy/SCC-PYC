<?php

declare(strict_types=1);

namespace Tests\Feature\Membership;

use App\Models\User;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberInvitation;
use App\Modules\Membership\Models\MemberSubmission;
use App\Modules\Membership\Services\MemberApprovalService;
use App\Modules\Membership\Services\MemberInvitationService;
use App\Modules\Membership\Services\MemberSyncService;
use App\Support\Security\PycRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Verifies all Sprint 2 Membership & Google Forms acceptance criteria (Spec Section 5 & 17.1).
 */
class MembershipAndGoogleSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        config()->set('pyc.membership.google_sheets.spreadsheet_id', 'sheet-12345');
        config()->set('pyc.membership.google_sheets.api_key', 'test-google-api-key');
        config()->set('notifications.sms.generic_http.endpoint', 'https://sms-sandbox.example/api/send');
        config()->set('notifications.sms.generic_http.api_token', 'sandbox-token');
        config()->set('notifications.email.mailer', 'array');
    }

    public function test_google_sync_imports_new_records_once_normalizes_fields_and_safely_handles_reruns(): void
    {
        Http::fake([
            'https://sheets.googleapis.com/*' => Http::response([
                'values' => [
                    ['Timestamp', 'Full Name', 'Phone Number', 'Email Address', 'Gender', 'Youth Group / Society'],
                    ['2026-11-25 08:00:00', 'Kwame Asante', '024 123 4567', 'KWAME.ASANTE@Example.org', 'M', 'CYO — Catholic Youth Organization'],
                    ['2026-11-25 08:05:00', 'Esi Owusu', '+233551112233', 'esi@example.org', 'Female', 'Youth Choir & Liturgical Music'],
                ],
            ], 200),
            'https://sms-sandbox.example/*' => Http::response(['message_id' => 'SMS-1', 'status' => 'accepted'], 200),
        ]);

        $secretary = User::factory()->withConfirmedTwoFactor()->create();
        $secretary->assignRole(PycRole::GeneralSecretary->value);

        $syncService = app(MemberSyncService::class);

        // First sync imports 2 pending applications, preserves raw payload, does NOT create portal User accounts
        $firstLog = $syncService->sync($secretary, 'manual');

        $this->assertSame('completed', $firstLog->status);
        $this->assertSame(2, $firstLog->fetched_count);
        $this->assertSame(2, $firstLog->imported_count);
        $this->assertSame(0, $firstLog->skipped_count);

        $kwame = Member::query()->where('email', 'kwame.asante@example.org')->firstOrFail();
        $this->assertSame('Kwame', $kwame->first_name);
        $this->assertSame('Asante', $kwame->last_name);
        $this->assertSame('+233241234567', $kwame->phone);
        $this->assertSame('male', $kwame->gender);
        $this->assertSame(MemberStatus::Pending->value, $kwame->status);
        $this->assertNull($kwame->member_number);
        $this->assertNull($kwame->account);

        $submission = MemberSubmission::query()->where('member_id', $kwame->id)->firstOrFail();
        $this->assertSame('KWAME.ASANTE@Example.org', $submission->raw_payload['Email Address']);

        // Rerun is strictly idempotent: 0 new imports, 2 skipped, still 2 total members
        $secondLog = $syncService->sync($secretary, 'scheduled');
        $this->assertSame(0, $secondLog->imported_count);
        $this->assertSame(2, $secondLog->skipped_count);
        $this->assertSame(2, Member::query()->count());
    }

    public function test_duplicate_submissions_are_detected_and_flagged_for_human_review(): void
    {
        $existing = Member::query()->create([
            'member_number' => 'PYC-2026-0001',
            'first_name' => 'Abena',
            'last_name' => 'Mensah',
            'phone' => '+233249998877',
            'email' => 'abena@example.org',
            'status' => MemberStatus::Active->value,
        ]);

        Http::fake([
            'https://sheets.googleapis.com/*' => Http::response([
                'values' => [
                    ['Response ID', 'First Name', 'Last Name', 'Phone Number', 'Email Address'],
                    ['RESP-DUP-01', 'Abena', 'Mensah-Bonsu', '0249998877', 'newemail@example.org'],
                ],
            ], 200),
            'https://sms-sandbox.example/*' => Http::response(['message_id' => 'SMS-DUP', 'status' => 'accepted'], 200),
        ]);

        $log = app(MemberSyncService::class)->sync();

        $this->assertSame(1, $log->imported_count);
        $this->assertSame(1, $log->duplicate_flagged_count);

        $flagged = Member::query()->where('email', 'newemail@example.org')->firstOrFail();
        $this->assertTrue($flagged->is_duplicate_flagged);
        $this->assertContains($existing->id, $flagged->duplicate_candidate_ids ?? []);
        $this->assertSame(MemberStatus::Pending->value, $flagged->status);
    }

    public function test_approval_generates_member_number_and_single_use_hashed_invitation_flow(): void
    {
        Http::fake([
            'https://sms-sandbox.example/*' => Http::response(['message_id' => 'SMS-INV', 'status' => 'accepted'], 200),
        ]);

        $pending = Member::query()->create([
            'first_name' => 'Kojo',
            'last_name' => 'Antwi',
            'phone' => '+233243334455',
            'email' => 'kojo.antwi@example.org',
            'status' => MemberStatus::Pending->value,
        ]);

        // Unapproved pending member cannot be invited prematurely (Spec 5.2)
        try {
            app(MemberInvitationService::class)->invite($pending);
            $this->fail('Expected InvalidArgumentException when inviting unapproved member.');
        } catch (InvalidArgumentException) {
            $this->assertSame(0, MemberInvitation::query()->count());
        }

        $secretary = User::factory()->withConfirmedTwoFactor()->create();
        $secretary->assignRole(PycRole::GeneralSecretary->value);

        // Secretary approves via API v1
        $this->actingAs($secretary)
            ->postJson("/api/v1/members/{$pending->id}/approve", [
                'reason' => 'Verified parishioner registration',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $pending->refresh();
        $this->assertNotNull($pending->member_number);
        $this->assertMatchesRegularExpression('/^PYC-\d{4}-0001$/', (string) $pending->member_number);
        $this->assertDatabaseCount('member_invitations', 1);

        // Issue fresh invitation directly via service to capture plain_token and verify SHA-256 hash storage
        $invResult = app(MemberInvitationService::class)->invite($pending, $secretary);
        $plainToken = $invResult['plain_token'];
        $invitation = $invResult['invitation'];

        $this->assertNotSame($plainToken, $invitation->token_hash);
        $this->assertSame(hash('sha256', $plainToken), $invitation->token_hash);

        // Member activates portal account via single-use invitation link
        $this->get(route('invitations.show', ['token' => $plainToken]))
            ->assertOk()
            ->assertSee($pending->member_number);

        $this->post(route('invitations.accept', ['token' => $plainToken]), [
            'email' => 'kojo.antwi@example.org',
            'password' => 'StrongPortalPass123',
            'password_confirmation' => 'StrongPortalPass123',
            'optional_consent' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('member_accounts', [
            'member_id' => $pending->id,
            'status' => 'active',
        ]);

        // Reusing the same single-use token is rejected
        auth()->logout();
        $this->get(route('invitations.show', ['token' => $plainToken]))
            ->assertOk()
            ->assertSee('Invitation Link Unavailable');
    }

    public function test_rejected_submissions_remain_traceable_and_auditable(): void
    {
        Http::fake([
            'https://sms-sandbox.example/*' => Http::response(['message_id' => 'SMS-REJ', 'status' => 'accepted'], 200),
        ]);

        $applicant = Member::query()->create([
            'first_name' => 'Efua',
            'last_name' => 'Sarpong',
            'phone' => '+233246667788',
            'email' => 'efua@example.org',
            'status' => MemberStatus::Pending->value,
        ]);

        $submission = MemberSubmission::query()->create([
            'source' => 'google_forms',
            'external_response_id' => 'RESP-REJ-101',
            'raw_payload' => ['Full Name' => 'Efua Sarpong', 'Phone' => '0246667788'],
            'sync_status' => 'imported',
            'member_id' => $applicant->id,
        ]);

        $secretary = User::factory()->withConfirmedTwoFactor()->create();
        $secretary->assignRole(PycRole::GeneralSecretary->value);

        $rejected = app(MemberApprovalService::class)->reject(
            member: $applicant,
            reviewer: $secretary,
            reason: 'Applicant belongs to a different parish',
        );

        $this->assertSame(MemberStatus::Rejected->value, $rejected->status);
        $this->assertNull($rejected->member_number);
        $this->assertDatabaseHas('member_submissions', ['id' => $submission->id, 'member_id' => $applicant->id]);
        $this->assertDatabaseHas('member_approvals', [
            'member_id' => $applicant->id,
            'decision' => 'rejected',
            'reason' => 'Applicant belongs to a different parish',
        ]);
        $this->assertDatabaseHas('membership_status_histories', [
            'member_id' => $applicant->id,
            'from_status' => 'pending',
            'to_status' => 'rejected',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'members.rejected',
            'actor_id' => $secretary->id,
        ]);
    }

    public function test_members_cannot_access_another_members_data_and_auditor_cannot_approve(): void
    {
        $memberOne = Member::query()->create([
            'member_number' => 'PYC-2026-0010',
            'first_name' => 'Yaw',
            'last_name' => 'Boateng',
            'email' => 'yaw@example.org',
            'status' => MemberStatus::Active->value,
        ]);

        $memberTwo = Member::query()->create([
            'member_number' => 'PYC-2026-0011',
            'first_name' => 'Akua',
            'last_name' => 'Danso',
            'email' => 'akua@example.org',
            'status' => MemberStatus::Active->value,
        ]);

        $userOne = User::factory()->create(['email' => 'yaw@example.org']);
        $userOne->assignRole(PycRole::Member->value);
        $memberOne->account()->create([
            'user_id' => $userOne->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Member One CAN view own profile (web & API)
        $this->actingAs($userOne)->get(route('membership.show', $memberOne))->assertOk();
        $this->actingAs($userOne)->getJson("/api/v1/members/{$memberOne->id}")->assertOk();

        // Member One CANNOT view Member Two's profile or full register (403 Forbidden)
        $this->actingAs($userOne)->get(route('membership.show', $memberTwo))->assertForbidden();
        $this->actingAs($userOne)->getJson("/api/v1/members/{$memberTwo->id}")->assertForbidden();
        $this->actingAs($userOne)->get(route('membership.index'))->assertForbidden();
        $this->actingAs($userOne)->getJson('/api/v1/members')->assertForbidden();

        // Auditor CAN view register (read-only) but CANNOT approve applications
        $auditor = User::factory()->withConfirmedTwoFactor()->create();
        $auditor->assignRole(PycRole::Auditor->value);

        $this->actingAs($auditor)->getJson('/api/v1/members')->assertOk();
        $this->actingAs($auditor)->postJson("/api/v1/members/{$memberTwo->id}/approve")->assertForbidden();
    }
}
