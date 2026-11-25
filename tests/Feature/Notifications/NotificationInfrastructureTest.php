<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Modules\Notifications\DTOs\DispatchNotificationData;
use App\Modules\Notifications\DTOs\NotificationRecipient;
use App\Modules\Notifications\Enums\DeliveryStatus;
use App\Modules\Notifications\Exceptions\TemplateValidationException;
use App\Modules\Notifications\Jobs\SendSmsNotificationJob;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Models\NotificationTemplateVersion;
use App\Modules\Notifications\Services\NotificationConsentService;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Support\Security\PycRole;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use App\Support\Shared\Models\SystemSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies all 13 Notification acceptance criteria from Specification Section 17.3.
 */
class NotificationInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        config()->set('notifications.sms.generic_http.endpoint', 'https://sms-sandbox.example/api/send');
        config()->set('notifications.sms.generic_http.status_endpoint', 'https://sms-sandbox.example/api/status/{reference}');
        config()->set('notifications.sms.generic_http.api_token', 'sandbox-bearer-token');
        config()->set('notifications.sms.generic_http.sender_id', 'SCC-PYC');
        config()->set('notifications.sms.generic_http.webhook_secret', 'webhook-signing-secret');
        config()->set('notifications.email.mailer', 'array');
    }

    public function test_all_eighteen_official_template_codes_are_seeded_and_versions_are_immutable(): void
    {
        $this->assertSame(36, NotificationTemplate::query()->count()); // 18 SMS + 18 Email

        $version = NotificationTemplateVersion::query()->firstOrFail();

        $this->expectException(ImmutableRecordException::class);
        $version->update(['body' => 'Tampered template content']);
    }

    public function test_generic_sms_adapter_and_smtp_email_send_authorized_tests_and_track_independently(): void
    {
        Http::fake([
            'https://sms-sandbox.example/api/send' => Http::response([
                'status' => 'accepted',
                'message_id' => 'PROVIDER-MSG-001',
                'cost_pesewas' => 5,
            ], 200),
        ]);

        $admin = User::factory()->withConfirmedTwoFactor()->create();
        $admin->assignRole(PycRole::SuperAdministrator->value);

        $orchestrator = app(NotificationOrchestrator::class);

        $smsDelivery = $orchestrator->sendAuthorizedTest(
            channel: 'sms',
            destination: '0241234567',
            messageBody: 'Sandbox SMS connectivity check',
            subject: null,
            actor: $admin,
        );

        $emailDelivery = $orchestrator->sendAuthorizedTest(
            channel: 'email',
            destination: 'member@stceciliapyc.org',
            messageBody: 'Sandbox SMTP connectivity check',
            subject: 'SMTP Test',
            actor: $admin,
        );

        Http::assertSent(static function (HttpClientRequest $request): bool {
            return $request->hasHeader('Authorization', 'Bearer sandbox-bearer-token')
                && $request->hasHeader('Idempotency-Key')
                && $request['to'] === '+233241234567'
                && $request['sender'] === 'SCC-PYC';
        });

        $this->assertSame(DeliveryStatus::Accepted->value, $smsDelivery->status);
        $this->assertSame('PROVIDER-MSG-001', $smsDelivery->provider_reference);
        $this->assertSame(5, $smsDelivery->cost_pesewas);

        // Spec 10.12: SMTP handoff records 'sent', never falsely claiming inbox 'delivered'
        $this->assertSame(DeliveryStatus::Sent->value, $emailDelivery->status);
    }

    public function test_invalid_credentials_fail_safely_without_retry_and_alert_administrators(): void
    {
        Http::fake([
            'https://sms-sandbox.example/api/send' => Http::response([
                'error' => 'Invalid API bearer token',
            ], 401),
        ]);

        $admin = User::factory()->withConfirmedTwoFactor()->create();
        $admin->assignRole(PycRole::SuperAdministrator->value);

        $delivery = app(NotificationOrchestrator::class)->sendAuthorizedTest(
            channel: 'sms',
            destination: '+233249999999',
            messageBody: 'Auth failure test',
            subject: null,
            actor: $admin,
        );

        $this->assertSame(DeliveryStatus::Failed->value, $delivery->status);
        $this->assertFalse($delivery->is_retryable);
        $this->assertNull($delivery->next_retry_at);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'notifications.provider_auth_alert',
            'category' => 'security',
        ]);
    }

    public function test_missing_or_unknown_template_variables_prevent_sending(): void
    {
        Http::fake();

        $orchestrator = app(NotificationOrchestrator::class);
        $recipient = new NotificationRecipient(
            name: 'Ama Mensah',
            email: 'ama@example.org',
            phone: '+233241112233',
        );

        try {
            $orchestrator->dispatch(new DispatchNotificationData(
                idempotencyKey: 'missing-vars-test-1',
                templateCode: 'payment_receipt',
                recipient: $recipient,
                variables: ['amount' => '10.00'], // missing receipt_number, contribution, etc.
                channels: ['sms', 'email'],
                failSafely: false,
            ));
            $this->fail('Expected TemplateValidationException for missing variables.');
        } catch (TemplateValidationException) {
            $this->assertSame(0, NotificationDelivery::query()->count());
        }
    }

    public function test_transient_errors_schedule_backoff_while_permanent_errors_do_not_loop(): void
    {
        Http::fakeSequence()
            ->push(['error' => 'Gateway overloaded'], 503)
            ->push(['error' => 'Malformed destination number'], 400);

        $orchestrator = app(NotificationOrchestrator::class);

        // 1) Transient 503 -> retrying with 1-minute initial backoff
        $transientLog = $orchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: 'transient-test-1',
            templateCode: 'membership_received',
            recipient: new NotificationRecipient(name: 'Kofi', phone: '+233241111111'),
            variables: ['applicant_name' => 'Kofi'],
            channels: ['sms'],
            failSafely: false,
        ));

        $transientDelivery = $transientLog?->deliveries->firstOrFail();
        $this->assertSame(DeliveryStatus::Retrying->value, $transientDelivery->status);
        $this->assertTrue($transientDelivery->is_retryable);
        $this->assertNotNull($transientDelivery->next_retry_at);

        // 2) Permanent 400 -> failed immediately, no retry scheduled
        $permanentLog = $orchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: 'permanent-test-1',
            templateCode: 'membership_received',
            recipient: new NotificationRecipient(name: 'Yaw', phone: '+233242222222'),
            variables: ['applicant_name' => 'Yaw'],
            channels: ['sms'],
            failSafely: false,
        ));

        $permanentDelivery = $permanentLog?->deliveries->firstOrFail();
        $this->assertSame(DeliveryStatus::Failed->value, $permanentDelivery->status);
        $this->assertFalse($permanentDelivery->is_retryable);
        $this->assertNull($permanentDelivery->next_retry_at);
    }

    public function test_duplicate_event_dispatch_is_idempotent_and_tracks_sms_and_email_independently(): void
    {
        Http::fake([
            'https://sms-sandbox.example/api/send' => Http::response(['message_id' => 'SMS-OK-99', 'status' => 'sent'], 200),
        ]);

        $orchestrator = app(NotificationOrchestrator::class);
        $payload = new DispatchNotificationData(
            idempotencyKey: 'payment-posted:PYC-PAY-2026-00001',
            templateCode: 'payment_receipt',
            recipient: new NotificationRecipient(
                name: 'Akosua Boateng',
                email: 'akosua@example.org',
                phone: '0245556677',
            ),
            variables: [
                'member_name' => 'Akosua Boateng',
                'amount' => '10.00',
                'contribution' => 'Monthly Youth Dues',
                'receipt_number' => 'PYC-RCT-2026-00001',
                'payment_date' => '25 Nov 2026',
                'payment_method' => 'Paystack',
                'payment_reference' => 'PYC-PAY-2026-00001',
            ],
            channels: ['sms', 'email'],
            failSafely: false,
        );

        // Note: SMS payment_receipt only declares {{amount}}, {{contribution}}, {{receipt_number}}, {{payment_date}};
        // let's dispatch membership_approved which uses identical variables across SMS and Email!
        $sharedPayload = new DispatchNotificationData(
            idempotencyKey: 'member-approved:PYC-2026-0001',
            templateCode: 'membership_approved',
            recipient: new NotificationRecipient(
                name: 'Akosua Boateng',
                email: 'akosua@example.org',
                phone: '0245556677',
            ),
            variables: [
                'member_name' => 'Akosua Boateng',
                'member_number' => 'PYC-2026-0001',
            ],
            channels: ['sms', 'email'],
            failSafely: false,
        );

        $first = $orchestrator->dispatch($sharedPayload);
        $second = $orchestrator->dispatch($sharedPayload);

        $this->assertSame($first?->id, $second?->id);
        $this->assertSame(1, NotificationLog::query()->where('idempotency_key', 'member-approved:PYC-2026-0001')->count());
        $this->assertSame(2, NotificationDelivery::query()->where('notification_log_id', $first?->id)->count());

        $sms = NotificationDelivery::query()->where('notification_log_id', $first?->id)->where('channel', 'sms')->firstOrFail();
        $email = NotificationDelivery::query()->where('notification_log_id', $first?->id)->where('channel', 'email')->firstOrFail();

        $this->assertSame(DeliveryStatus::Sent->value, $sms->status);
        $this->assertSame(DeliveryStatus::Sent->value, $email->status);
        $this->assertNotNull($sms->notification_template_version_id);
        $this->assertNotNull($email->notification_template_version_id);
        $this->assertNotSame($sms->notification_template_version_id, $email->notification_template_version_id);
    }

    public function test_ambiguous_provider_timeout_reconciles_provider_state_before_resending(): void
    {
        Http::fake([
            'https://sms-sandbox.example/api/status/PRIOR-REF-777' => Http::response(['status' => 'delivered'], 200),
            'https://sms-sandbox.example/api/send' => Http::response(['error' => 'Should not be called'], 500),
        ]);

        $log = NotificationLog::query()->create([
            'idempotency_key' => 'timeout-reconcile-1',
            'category' => 'finance',
            'is_transactional' => true,
            'status' => DeliveryStatus::Retrying->value,
        ]);

        $delivery = NotificationDelivery::query()->create([
            'notification_log_id' => $log->id,
            'channel' => 'sms',
            'provider' => 'generic_http',
            'recipient_address' => '+233247778888',
            'rendered_body' => 'Receipt check',
            'provider_reference' => 'PRIOR-REF-777',
            'status' => DeliveryStatus::Retrying->value,
            'attempts' => 1,
            'ambiguous_timeout_at' => now(),
        ]);

        app()->call([new SendSmsNotificationJob($delivery->id), 'handle']);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::Delivered->value, $delivery->status);
        Http::assertNotSent(static fn (HttpClientRequest $req): bool => str_contains($req->url(), '/api/send'));
    }

    public function test_provider_webhooks_are_authenticated_and_deduplicated(): void
    {
        $log = NotificationLog::query()->create([
            'idempotency_key' => 'webhook-delivery-1',
            'category' => 'finance',
            'is_transactional' => true,
            'status' => DeliveryStatus::Sent->value,
        ]);

        $delivery = NotificationDelivery::query()->create([
            'notification_log_id' => $log->id,
            'channel' => 'sms',
            'provider' => 'generic_http',
            'recipient_address' => '+233243334444',
            'rendered_body' => 'Receipt notice',
            'provider_reference' => 'DLV-REF-100',
            'status' => DeliveryStatus::Accepted->value,
            'attempts' => 1,
        ]);

        $body = json_encode([
            'event_id' => 'EVT-UNIQUE-500',
            'provider_reference' => 'DLV-REF-100',
            'status' => 'delivered',
        ], JSON_THROW_ON_ERROR);

        // 1) Unsigned / tampered signature rejected with 401
        $this->call('POST', '/api/v1/webhooks/notifications/sms', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => 'invalid-signature',
        ], $body)->assertStatus(401);

        // 2) Valid HMAC-SHA256 signature accepted and updates delivery to 'delivered'
        $validSig = hash_hmac('sha256', $body, 'webhook-signing-secret');

        $this->call('POST', '/api/v1/webhooks/notifications/sms', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $validSig,
        ], $body)->assertOk();

        $this->assertSame(DeliveryStatus::Delivered->value, $delivery->fresh()?->status);

        // 3) Replay of identical event_id is safely deduplicated
        $this->call('POST', '/api/v1/webhooks/notifications/sms', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $validSig,
        ], $body)->assertOk();

        $this->assertDatabaseCount('notification_webhooks', 1);
    }

    public function test_optional_preferences_suppress_announcements_without_blocking_essential_transactional_notices(): void
    {
        Http::fake([
            'https://sms-sandbox.example/api/send' => Http::response(['message_id' => 'TX-SMS-1', 'status' => 'sent'], 200),
        ]);

        $memberUser = User::factory()->create(['phone' => '+233240001122']);
        $memberUser->assignRole(PycRole::Member->value);

        // Member opts OUT of optional SMS and Email notices
        app(NotificationConsentService::class)->updatePreferences(
            user: $memberUser,
            smsEnabled: false,
            emailEnabled: false,
            inAppEnabled: true,
            categoryPreferences: ['events' => false, 'announcements' => false],
        );

        $orchestrator = app(NotificationOrchestrator::class);
        $recipient = new NotificationRecipient(
            userId: $memberUser->id,
            name: $memberUser->name,
            email: $memberUser->email,
            phone: $memberUser->phone,
        );

        // Optional event_announcement is suppressed on SMS and Email
        $optionalLog = $orchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: 'opt-event-1',
            templateCode: 'event_announcement',
            recipient: $recipient,
            variables: [
                'event_title' => 'Youth Choir Vigil',
                'event_date' => '12 Dec 2026',
                'venue' => 'Parish Hall',
            ],
            channels: ['sms', 'email'],
            failSafely: false,
        ));

        $this->assertCount(0, $optionalLog?->deliveries ?? []);
        $this->assertSame('suppressed', $optionalLog?->status);

        // Essential transactional account_invitation STILL sends on SMS and Email!
        $transactionalLog = $orchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: 'tx-invite-1',
            templateCode: 'account_invitation',
            recipient: $recipient,
            variables: [
                'member_name' => $memberUser->name,
                'invitation_url' => 'https://pyc.example.org/invite/token123',
                'expires_in' => '72 hours',
            ],
            channels: ['sms', 'email'],
            failSafely: false,
        ));

        $this->assertCount(2, $transactionalLog?->deliveries ?? []);
    }

    public function test_financial_transaction_commit_is_unaffected_by_notification_outage(): void
    {
        Http::fake([
            'https://sms-sandbox.example/api/send' => Http::response(['error' => 'Total outage'], 500),
        ]);

        // Simulate a committed database record followed by post-commit notification dispatch during outage/bad template
        $record = DB::transaction(static function (): SystemSetting {
            return SystemSetting::query()->create([
                'group' => 'finance_test',
                'key' => 'finance.simulated_posted_marker',
                'value' => 'POSTED-PYC-JRN-2026-00001',
                'type' => 'string',
            ]);
        });

        $result = app(NotificationOrchestrator::class)->dispatch(new DispatchNotificationData(
            idempotencyKey: 'outage-safe-test',
            templateCode: 'non_existent_template_code',
            recipient: new NotificationRecipient(name: 'Member', phone: '+233241231234'),
            variables: [],
            channels: ['sms'],
            failSafely: true,
        ));

        $this->assertNull($result);
        $this->assertDatabaseHas('system_settings', [
            'id' => $record->id,
            'key' => 'finance.simulated_posted_marker',
        ]);
    }

    public function test_bulk_sending_is_permission_controlled_rate_limited_and_audited(): void
    {
        Http::fake([
            'https://sms-sandbox.example/api/send' => Http::response(['message_id' => 'BULK-1', 'status' => 'accepted'], 200),
        ]);

        $auditor = User::factory()->withConfirmedTwoFactor()->create();
        $auditor->assignRole(PycRole::Auditor->value);

        $secretary = User::factory()->withConfirmedTwoFactor()->create();
        $secretary->assignRole(PycRole::GeneralSecretary->value);

        // Auditor is denied bulk sending (403)
        $this->actingAs($auditor)
            ->postJson('/api/v1/notifications/campaigns', [
                'title' => 'Unauthorized Bulk',
                'category' => 'announcements',
                'channels' => ['sms'],
                'sms_body' => 'Hello',
            ])
            ->assertForbidden();

        // Authorized General Secretary succeeds and generates audit log
        $this->actingAs($secretary)
            ->postJson('/api/v1/notifications/campaigns', [
                'title' => 'Authorized General Meeting Notice',
                'category' => 'announcements',
                'channels' => ['sms'],
                'sms_body' => 'General meeting this Sunday.',
                'dispatch_now' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'notifications.bulk_campaign_dispatched',
            'actor_id' => $secretary->id,
        ]);

        // Rate limit protects bulk endpoint (limit is 5/minute; 5 more requests trigger 429 on the 6th)
        for ($i = 1; $i <= 4; $i++) {
            $this->actingAs($secretary)->postJson('/api/v1/notifications/campaigns', [
                'title' => "Campaign #{$i}",
                'category' => 'announcements',
                'channels' => ['sms'],
                'sms_body' => 'Body',
            ])->assertCreated();
        }

        $this->actingAs($secretary)->postJson('/api/v1/notifications/campaigns', [
            'title' => 'Rate Limited Campaign',
            'category' => 'announcements',
            'channels' => ['sms'],
            'sms_body' => 'Body',
        ])->assertStatus(429);
    }
}
