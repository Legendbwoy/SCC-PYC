<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use App\Support\Shared\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AuditAndSettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_are_append_only_and_redact_secrets(): void
    {
        $actor = User::factory()->create();
        $logger = app(AuditLogger::class);

        $entry = $logger->log(
            event: 'test.event',
            category: 'security',
            description: 'Testing append-only audit trail',
            auditable: $actor,
            newValues: [
                'email' => $actor->email,
                'api_token' => 'super-secret-token',
                'password' => 'hunter2',
            ],
            actor: $actor,
        );

        $this->assertSame('[REDACTED]', $entry->new_values['api_token']);
        $this->assertSame('[REDACTED]', $entry->new_values['password']);
        $this->assertSame($actor->email, $entry->new_values['email']);

        try {
            $entry->update(['description' => 'Tampered']);
            $this->fail('Expected ImmutableRecordException when updating AuditLog.');
        } catch (ImmutableRecordException) {
            $this->assertDatabaseHas('audit_logs', [
                'id' => $entry->id,
                'description' => 'Testing append-only audit trail',
            ]);
        }

        $this->expectException(ImmutableRecordException::class);
        $entry->delete();
    }

    public function test_system_settings_reject_secret_keys_and_audit_changes(): void
    {
        $actor = User::factory()->create();
        $settings = app(SystemSettingService::class);

        $settings->set(
            key: 'organization.name',
            value: 'St. Cecilia Parish Youth Council',
            group: 'organization',
            isPublic: true,
            actor: $actor,
        );

        $this->assertSame('St. Cecilia Parish Youth Council', $settings->get('organization.name'));
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'settings.updated',
            'actor_id' => $actor->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $settings->set(
            key: 'sms.api_token',
            value: 'raw-secret-token',
            group: 'notifications',
            actor: $actor,
        );
    }
}
