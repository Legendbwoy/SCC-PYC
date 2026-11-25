<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parish youth events & registrations schema (Spec Section 9.1 & 11.1).
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique(); // PYC-EVT-2026-00001
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('venue', 180);
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('registration_deadline')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('allow_registration')->default(true);
            $table->boolean('allow_cancellation')->default(true);
            $table->foreignId('contribution_campaign_id')
                ->nullable()
                ->constrained('contribution_campaigns')
                ->restrictOnDelete();
            $table->string('status', 24)->default('draft')->index(); // draft | published | cancelled | completed
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamp('announced_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('event_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('member_obligation_id')
                ->nullable()
                ->constrained('member_obligations')
                ->nullOnDelete();
            $table->string('status', 24)->default('registered')->index(); // registered | cancelled | attended | absent
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'member_id'], 'er_event_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
    }
};
