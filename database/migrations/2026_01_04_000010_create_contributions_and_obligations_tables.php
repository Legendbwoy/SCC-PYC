<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contributions, monthly dues, obligations, and adjustments schema (Spec Section 6.2, 6.3 & 11.1).
     */
    public function up(): void
    {
        Schema::create('contribution_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique(); // monthly_dues | event_contribution | special_collection | donation
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->string('default_income_account_code', 16)->default('4000');
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('contribution_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contribution_type_id')
                ->constrained('contribution_types')
                ->restrictOnDelete();
            $table->string('code', 80)->unique();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_pesewas'); // e.g. 1000 = GHS 10.00
            $table->string('currency', 8)->default('GHS');
            $table->string('frequency', 24)->default('one_off')->index(); // monthly | one_off | annual
            $table->date('effective_from')->index();
            $table->date('effective_to')->nullable()->index();
            $table->unsignedTinyInteger('due_day_of_month')->default(28);
            $table->boolean('allow_partial_payments')->default(true);
            $table->boolean('allow_advance_payments')->default(true);
            $table->string('status', 24)->default('active')->index(); // active | paused | closed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('member_obligations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();
            $table->foreignId('contribution_campaign_id')
                ->constrained('contribution_campaigns')
                ->restrictOnDelete();
            $table->string('period_key', 32)->index(); // e.g. 2026-11 or ONE-OFF
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->unsignedBigInteger('amount_due_pesewas');
            $table->unsignedBigInteger('amount_allocated_pesewas')->default(0);
            $table->bigInteger('adjustment_pesewas')->default(0); // negative reduces amount owed (waiver/exemption)
            $table->string('currency', 8)->default('GHS');
            $table->string('status', 24)->default('unpaid')->index(); // unpaid | partially_paid | paid | waived | exempt
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(
                ['member_id', 'contribution_campaign_id', 'period_key'],
                'mo_member_campaign_period_unique'
            );
        });

        Schema::create('obligation_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_obligation_id')
                ->constrained('member_obligations')
                ->restrictOnDelete();
            $table->string('adjustment_type', 32)->index(); // waiver | exemption | discount | surcharge
            $table->bigInteger('amount_pesewas'); // negative reduces obligation; positive increases
            $table->text('reason');
            $table->string('status', 24)->default('approved')->index(); // pending_approval | approved | rejected
            $table->string('approval_reference', 64)->nullable()->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_adjustments');
        Schema::dropIfExists('member_obligations');
        Schema::dropIfExists('contribution_campaigns');
        Schema::dropIfExists('contribution_types');
    }
};
