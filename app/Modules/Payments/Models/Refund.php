<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'reference',
        'payment_id',
        'amount_pesewas',
        'currency',
        'reason',
        'refund_channel',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'processed_at',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_pesewas' => 'integer',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
