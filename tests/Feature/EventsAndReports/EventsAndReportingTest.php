<?php

declare(strict_types=1);

namespace Tests\Feature\EventsAndReports;

use App\Models\User;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Payments\Services\PaymentService;
use App\Support\Security\PycRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies Sprint 7 Events, Shared Event Contributions, Attendance & Reconciled Reports/Exports (Spec Section 9 & 14).
 */
class EventsAndReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        config()->set('notifications.sms.generic_http.endpoint', 'https://sms-sandbox.example/api/send');
        config()->set('notifications.sms.generic_http.api_token', 'sandbox-token');
        config()->set('notifications.email.mailer', 'array');
    }

    public function test_event_contributions_reuse_shared_obligation_and_ledger_engine_and_enforce_capacity(): void
    {
        Http::fake([
            'https://sms-sandbox.example/*' => Http::response(['message_id' => 'SMS-EVT', 'status' => 'accepted'], 200),
        ]);

        $secretary = User::factory()->withConfirmedTwoFactor()->create();
        $secretary->assignRole(PycRole::GeneralSecretary->value);

        $finSec = User::factory()->withConfirmedTwoFactor()->create();
        $finSec->assignRole(PycRole::FinancialSecretary->value);

        $president = User::factory()->withConfirmedTwoFactor()->create();
        $president->assignRole(PycRole::President->value);

        // 1) Secretary creates a capacity-limited Youth Retreat with a GHS 25.00 (2,500p) contribution
        $createResp = $this->actingAs($secretary)
            ->postJson('/api/v1/events', [
                'title' => 'Annual Youth Lenten Retreat',
                'venue' => 'St. Cecilia Retreat Centre',
                'starts_at' => now()->addDays(14)->toDateTimeString(),
                'capacity' => 1,
                'contribution_amount_pesewas' => 2500,
                'status' => 'published',
            ])
            ->assertCreated();

        $eventId = (int) $createResp->json('data.id');
        $event = PycEvent::query()->with('contributionCampaign')->findOrFail($eventId);
        $this->assertMatchesRegularExpression('/^PYC-EVT-\d{4}-00001$/', $event->reference);
        $this->assertNotNull($event->contributionCampaign);
        $this->assertSame(2500, $event->contributionCampaign?->amount_pesewas);

        // 2) Member 1 registers -> creates shared MemberObligation (2,500p)
        $memberOne = Member::query()->create([
            'member_number' => 'PYC-2026-0001',
            'first_name' => 'Mansa',
            'last_name' => 'Musa',
            'phone' => '+233241002000',
            'email' => 'mansa@example.org',
            'status' => MemberStatus::Active->value,
        ]);
        $userOne = User::factory()->create(['email' => 'mansa@example.org']);
        $userOne->assignRole(PycRole::Member->value);
        $memberOne->account()->create(['user_id' => $userOne->id, 'status' => 'active', 'activated_at' => now()]);

        $this->actingAs($userOne)
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertCreated();

        $registration = EventRegistration::query()->where('event_id', $event->id)->where('member_id', $memberOne->id)->firstOrFail();
        $this->assertNotNull($registration->member_obligation_id);

        // 3) Capacity = 1 blocks a second member from registering
        $memberTwo = Member::query()->create([
            'member_number' => 'PYC-2026-0002',
            'first_name' => 'Second',
            'last_name' => 'Member',
            'email' => 'second@example.org',
            'status' => MemberStatus::Active->value,
        ]);
        $userTwo = User::factory()->create(['email' => 'second@example.org']);
        $userTwo->assignRole(PycRole::Member->value);
        $memberTwo->account()->create(['user_id' => $userTwo->id, 'status' => 'active', 'activated_at' => now()]);

        $this->actingAs($userTwo)
            ->postJson("/api/v1/events/{$event->id}/register")
            ->assertStatus(500);

        // 4) Paying the event contribution posts to 4100 Event Contributions Income via shared PaymentService!
        $paymentService = app(PaymentService::class);
        $cashPayment = $paymentService->recordCashPayment($memberOne, 2500, $finSec, 'Retreat fee');
        $approvedPayment = $paymentService->approveCashPayment($cashPayment, $president, 'Approved retreat cash');

        $this->assertSame('posted', $approvedPayment->payment_status);
        $this->assertSame('paid', $registration->obligation?->fresh()?->status);

        // 5) Secretary marks attendance
        $this->actingAs($secretary)
            ->postJson("/api/v1/events/registrations/{$registration->id}/attendance", ['attended' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'attended');
    }

    public function test_auditor_can_view_and_export_reconciled_pdf_and_csv_reports_while_member_is_blocked(): void
    {
        $finSec = User::factory()->withConfirmedTwoFactor()->create();
        $finSec->assignRole(PycRole::FinancialSecretary->value);

        $president = User::factory()->withConfirmedTwoFactor()->create();
        $president->assignRole(PycRole::President->value);

        $auditor = User::factory()->withConfirmedTwoFactor()->create();
        $auditor->assignRole(PycRole::Auditor->value);

        $memberUser = User::factory()->create();
        $memberUser->assignRole(PycRole::Member->value);

        $member = Member::query()->create([
            'member_number' => 'PYC-2026-0050',
            'first_name' => 'Nana',
            'last_name' => 'Konadu',
            'status' => MemberStatus::Active->value,
        ]);

        // Post GHS 10.00 dues cash payment so ledger has authoritative entries
        $cash = app(PaymentService::class)->recordCashPayment($member, 1000, $finSec);
        app(PaymentService::class)->approveCashPayment($cash, $president);

        // Member is blocked from executive reports
        $this->actingAs($memberUser)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($memberUser)->get(route('reports.export', ['format' => 'pdf', 'report' => 'trial_balance']))->assertForbidden();

        // Auditor views dashboard and exports Trial Balance PDF + Income/Expenditure Excel CSV
        $this->actingAs($auditor)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Executive & Auditor Reports');

        $this->actingAs($auditor)
            ->get(route('reports.export', ['format' => 'pdf', 'report' => 'trial_balance']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $csvResponse = $this->actingAs($auditor)
            ->get(route('reports.export', ['format' => 'csv', 'report' => 'income_expenditure']))
            ->assertOk();

        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('Monthly Dues Income', $csvContent);
        $this->assertStringContainsString('GHS 10.00', $csvContent);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'reports.exported_pdf',
            'actor_id' => $auditor->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'reports.exported_excel_csv',
            'actor_id' => $auditor->id,
        ]);
    }
}
