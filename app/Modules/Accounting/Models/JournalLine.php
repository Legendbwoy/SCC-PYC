<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Support\Shared\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable double-entry journal line stored in integer pesewas (Spec Section 8.1).
 */
class JournalLine extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'journal_entry_id',
        'financial_account_id',
        'debit_pesewas',
        'credit_pesewas',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'debit_pesewas' => 'integer',
            'credit_pesewas' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new ImmutableRecordException('Posted journal lines are immutable and cannot be modified.');
        });

        static::deleting(static function (): never {
            throw new ImmutableRecordException('Posted journal lines are immutable and cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }
}
