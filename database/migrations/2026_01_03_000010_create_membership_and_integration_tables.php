<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membership & Google Forms/Sheets integration tables (Spec Section 5.5, 11.1, 11.3).
     */
    public function up(): void
    {
        Schema::create('integration_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 64)->unique(); // e.g. google_sheets
            $table->string('name', 150);
            $table->string('spreadsheet_id', 160)->nullable();
            $table->string('sheet_range', 120)->default('Form Responses 1!A1:Z2000');
            $table->json('field_mapping')->nullable();
            $table->string('last_cursor', 120)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('status', 32)->default('configured')->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 64)->index();
            $table->string('trigger_type', 32)->default('manual'); // manual | scheduled | retry
            $table->string('status', 32)->default('running')->index(); // running | completed | completed_with_errors | failed
            $table->unsignedInteger('fetched_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('duplicate_flagged_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('error_summary', 500)->nullable();
            $table->json('error_details')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->string('member_number', 32)->nullable()->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('phone', 32)->nullable()->index();
            $table->string('email', 190)->nullable()->index();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 24)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 32)->nullable();
            $table->date('joined_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->string('status', 32)->default('pending')->index(); // pending | active | rejected | archived
            $table->boolean('is_duplicate_flagged')->default(false)->index();
            $table->json('duplicate_candidate_ids')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });

        Schema::create('member_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sync_log_id')->nullable()->constrained('sync_logs')->nullOnDelete();
            $table->string('source', 40)->index(); // google_forms | manual
            $table->string('external_response_id', 160);
            $table->json('raw_payload');
            $table->json('normalized_payload')->nullable();
            $table->string('sync_status', 32)->default('imported')->index(); // imported | duplicate_flagged | validation_failed | skipped
            $table->json('validation_errors')->nullable();
            $table->json('duplicate_candidate_ids')->nullable();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_response_id'], 'ms_source_external_id_unique');
        });

        Schema::create('member_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 24)->index(); // approved | rejected
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('member_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 120)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('member_group_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('member_group_id')->constrained('member_groups')->restrictOnDelete();
            $table->string('role_title', 80)->nullable();
            $table->date('assigned_at');
            $table->date('ended_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'member_group_id']);
        });

        Schema::create('member_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained('members')->restrictOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default('active')->index(); // active | suspended
            $table->timestamp('activated_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('member_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('email', 190)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('membership_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->index();
            $table->string('reason', 255)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_status_histories');
        Schema::dropIfExists('member_invitations');
        Schema::dropIfExists('member_accounts');
        Schema::dropIfExists('member_group_assignments');
        Schema::dropIfExists('member_groups');
        Schema::dropIfExists('member_approvals');
        Schema::dropIfExists('member_submissions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('integration_connections');
    }
};
