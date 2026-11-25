<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments, allocations, webhooks, receipts, refunds, settlements & approvals (Spec Section 7.4, 7.5 & 11.1).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique(); // PYC-PAY-2026-00001
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->unsignedBigInteger('amount_pesewas');
            $table->string('currency', 8)->default('GHS');
            $table->string('method', 24)->index(); // paystack | cash
            $table->string('payment_status', 24)->default('pending')->index(); // pending | verified | failed | posted | reversed | refunded
            $table->string('approval_status', 24)->default('not_required')->index(); // not_required | awaiting_approval | approved | rejected
            $table->string('provider_reference', 160)->nullable()->unique();
            $table->string('provider_authorization_url', 500)->nullable();
            $table->json('requested_allocations')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('posted_at')->nullable()->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->foreignId('member_obligation_id')->constrained('member_obligations')->restrictOnDelete();
            $table->unsignedBigInteger('amount_pesewas');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_id', 'member_obligation_id']);
        });

        Schema::create('payment_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->default('paystack');
            $table->string('provider_event_id', 160);
            $table->string('event_type', 80)->index();
            $table->string('reference', 160)->nullable()->index();
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->string('processing_status', 24)->default('received')->index(); // received | processed | ignored | failed
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id'], 'pw_provider_event_unique');
        });

        Schema::create('receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_number', 40)->unique(); // PYC-RCT-2026-00001
            $table->foreignId('payment_id')->unique()->constrained('payments')->restrictOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->unsignedBigInteger('amount_pesewas');
            $table->string('currency', 8)->default('GHS');
            $table->timestamp('issued_at')->useCurrent()->index();
            $table->json('snapshot');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique(); // PYC-REF-2026-00001
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->unsignedBigInteger('amount_pesewas');
            $table->string('currency', 8)->default('GHS');
            $table->text('reason');
            $table->string('refund_channel', 24)->default('bank'); // cash | bank | paystack
            $table->string('status', 24)->default('processed')->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('settlements', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 40)->default('paystack');
            $table->string('provider_settlement_reference', 160)->unique();
            $table->unsignedBigInteger('gross_amount_pesewas');
            $table->unsignedBigInteger('fee_amount_pesewas')->default(0);
            $table->unsignedBigInteger('net_amount_pesewas');
            $table->string('currency', 8)->default('GHS');
            $table->date('settlement_date')->index();
            $table->string('status', 24)->default('posted')->index();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('financial_approvals', function (Blueprint $table): void {
            $table->id();
            $table->string('approvable_type', 160);
            $table->unsignedBigInteger('approvable_id');
            $table->string('action_type', 40)->index(); // cash_payment | expense | refund | journal_reversal
            $table->string('decision', 24)->index(); // approved | rejected
            $table->text('reason')->nullable();
            $table->foreignId('initiator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_approvals');
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_webhooks');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
