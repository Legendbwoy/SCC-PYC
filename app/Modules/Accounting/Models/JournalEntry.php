<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Models\User;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Immutable double-entry journal header (Spec Section 3.5 & 8.1).
 * Posted journals can never be edited or deleted; corrections use reversing journals.
 */
class JournalEntry extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'journal_number',
        'financial_period_id',
        'entry_date',
        'source_type',
        'source_id',
        'idempotency_key',
        'narration',
        'reversal_of_journal_id',
        'reversal_reason',
        'posted_by',
        'posted_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new ImmutableRecordException('Posted journal entries are immutable and cannot be modified.');
        });

        static::deleting(static function (): never {
            throw new ImmutableRecordException('Posted journal entries are immutable and cannot be deleted.');
        });
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * @return BelongsTo<FinancialPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'financial_period_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function reversedJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_journal_id');
    }

    /**
     * @return HasOne<self, $this>
     */
    public function reversingEntry(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_journal_id');
    }
}
