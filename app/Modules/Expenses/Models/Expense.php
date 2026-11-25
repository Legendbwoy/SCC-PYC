<?php

declare(strict_types=1);

namespace App\Modules\Expenses\Models;

use App\Models\User;
use App\Modules\Accounting\Models\JournalEntry;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use App\Support\Shared\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'reference',
        'expense_category_id',
        'payee_name',
        'payee_contact',
        'description',
        'amount_pesewas',
        'currency',
        'expense_date',
        'payment_channel',
        'status',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'journal_entry_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_pesewas' => 'integer',
            'expense_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(static function (self $expense): void {
            if ($expense->journal_entry_id !== null || $expense->status === 'paid') {
                throw new ImmutableRecordException('Posted expenses cannot be deleted; use journal reversals.');
            }
        });
    }

    public function amountMoney(): Money
    {
        return Money::fromPesewas($this->amount_pesewas, $this->currency);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
