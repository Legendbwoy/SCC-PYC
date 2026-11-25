<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialPeriod extends Model
{
    protected $fillable = [
        'period_key',
        'period_type',
        'year',
        'month',
        'starts_at',
        'ends_at',
        'status',
        'closed_at',
        'closed_by',
        'closure_notes',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * @return HasMany<JournalEntry, $this>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
