<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notification infrastructure tables (Spec Section 10.11 & 11.1).
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->index();
            $table->string('name', 150);
            $table->string('category', 40)->index(); // membership | events | finance | security | general
            $table->string('channel', 24)->index(); // sms | email | in_app
            $table->boolean('is_transactional')->default(true)->index();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['code', 'channel']);
        });

        Schema::create('notification_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_template_id')
                ->constrained('notification_templates')
                ->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->json('required_variables');
            $table->string('change_notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['notification_template_id', 'version_number'], 'ntv_template_version_unique');
        });

        Schema::table('notification_templates', function (Blueprint $table): void {
            $table->foreign('current_version_id', 'nt_current_version_fk')
                ->references('id')
                ->on('notification_template_versions')
                ->nullOnDelete();
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->json('category_preferences')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->string('recipient_identifier', 180)->index();
            $table->string('channel', 24)->index(); // sms | email
            $table->string('purpose', 64)->index(); // transactional | events | announcements
            $table->string('status', 24)->index(); // granted | withdrawn
            $table->string('lawful_basis', 64)->default('consent'); // consent | membership_obligation | legitimate_interest
            $table->string('source', 80); // portal_preferences | registration_form | admin_recorded
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('title', 180);
            $table->string('category', 40)->default('announcements')->index();
            $table->json('channels');
            $table->json('audience_filter')->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('sms_body')->nullable();
            $table->text('email_body')->nullable();
            $table->string('status', 32)->default('draft')->index(); // draft | scheduled | processing | completed | cancelled
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 160)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->foreignId('campaign_id')->nullable()->constrained('notification_campaigns')->nullOnDelete();
            $table->string('template_code', 80)->nullable()->index();
            $table->string('category', 40)->index();
            $table->boolean('is_transactional')->default(true);
            $table->string('event_name', 120)->nullable()->index();
            $table->string('related_type', 160)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->json('variables')->nullable();
            $table->string('status', 32)->default('queued')->index();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_log_id')
                ->constrained('notification_logs')
                ->restrictOnDelete();
            $table->foreignId('notification_template_version_id')
                ->nullable()
                ->constrained('notification_template_versions')
                ->restrictOnDelete();
            $table->string('channel', 24)->index(); // sms | email | in_app
            $table->string('provider', 64)->index();
            $table->string('recipient_address', 190)->index();
            $table->string('recipient_name', 160)->nullable();
            $table->string('rendered_subject', 255)->nullable();
            $table->text('rendered_body');
            $table->unsignedSmallInteger('gsm_segments')->nullable();
            $table->string('provider_reference', 160)->nullable()->index();
            $table->string('status', 32)->default('queued')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedInteger('cost_pesewas')->nullable();
            $table->string('cost_currency', 8)->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('is_retryable')->default(false);
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamp('ambiguous_timeout_at')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->unique(['notification_log_id', 'channel'], 'nd_log_channel_unique');
        });

        Schema::create('notification_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 64);
            $table->string('provider_event_id', 160);
            $table->string('payload_hash', 64)->index();
            $table->json('payload');
            $table->string('processing_status', 32)->default('received')->index(); // received | processed | ignored | failed
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id'], 'nw_provider_event_unique');
        });

        Schema::create('notification_suppressions', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 24);
            $table->string('recipient_address', 190);
            $table->string('reason', 160);
            $table->string('source', 64)->default('system');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['channel', 'recipient_address'], 'ns_channel_recipient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_suppressions');
        Schema::dropIfExists('notification_webhooks');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_campaigns');
        Schema::dropIfExists('notification_consents');
        Schema::dropIfExists('notification_preferences');

        if (Schema::hasTable('notification_templates')) {
            Schema::table('notification_templates', function (Blueprint $table): void {
                $table->dropForeign('nt_current_version_fk');
            });
        }

        Schema::dropIfExists('notification_template_versions');
        Schema::dropIfExists('notification_templates');
    }
};
