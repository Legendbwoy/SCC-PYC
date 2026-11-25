<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Double-entry accounting & expense management tables (Spec Section 8.5, 11.1 & 11.3).
     */
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 140);
            $table->string('type', 24)->index(); // asset | liability | equity | income | expense
            $table->string('normal_balance', 12); // debit | credit
            $table->string('parent_code', 16)->nullable()->index();
            $table->boolean('is_postable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('financial_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('period_key', 24)->unique(); // e.g. 2026-11 or 2026
            $table->string('period_type', 16)->default('monthly')->index(); // monthly | annual
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('month')->nullable();
            $table->date('starts_at')->index();
            $table->date('ends_at')->index();
            $table->string('status', 16)->default('open')->index(); // open | closed
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('closure_notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('journal_number', 40)->unique(); // PYC-JRN-2026-00001
            $table->foreignId('financial_period_id')->constrained('financial_periods')->restrictOnDelete();
            $table->date('entry_date')->index();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('idempotency_key', 160)->unique();
            $table->string('narration', 500);
            $table->foreignId('reversal_of_journal_id')
                ->nullable()
                ->unique()
                ->constrained('journal_entries')
                ->restrictOnDelete();
            $table->string('reversal_reason', 500)->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('posted_at')->useCurrent()->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['source_type', 'source_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('debit_pesewas')->default(0);
            $table->unsignedBigInteger('credit_pesewas')->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['financial_account_id', 'journal_entry_id']);
        });

        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique(); // PYC-EXP-2026-00001
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('payee_name', 160);
            $table->string('payee_contact', 80)->nullable();
            $table->text('description');
            $table->unsignedBigInteger('amount_pesewas');
            $table->string('currency', 8)->default('GHS');
            $table->date('expense_date')->index();
            $table->string('payment_channel', 24)->default('cash'); // cash | bank
            $table->string('status', 24)->default('draft')->index(); // draft | submitted | approved | rejected | paid
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->date('statement_date')->index();
            $table->bigInteger('statement_balance_pesewas');
            $table->bigInteger('ledger_balance_pesewas');
            $table->string('status', 24)->default('draft')->index(); // draft | reconciled
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('financial_periods');
        Schema::dropIfExists('financial_accounts');
    }
};
