<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    protected $fillable = [
        'provider',
        'provider_settlement_reference',
        'gross_amount_pesewas',
        'fee_amount_pesewas',
        'net_amount_pesewas',
        'currency',
        'settlement_date',
        'status',
        'journal_entry_id',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount_pesewas' => 'integer',
            'fee_amount_pesewas' => 'integer',
            'net_amount_pesewas' => 'integer',
            'settlement_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
