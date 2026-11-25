<?php

declare(strict_types=1);

namespace App\Modules\Membership\Models;

use App\Modules\Integrations\Models\SyncLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSubmission extends Model
{
    protected $fillable = [
        'sync_log_id',
        'source',
        'external_response_id',
        'raw_payload',
        'normalized_payload',
        'sync_status',
        'validation_errors',
        'duplicate_candidate_ids',
        'member_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'normalized_payload' => 'array',
            'validation_errors' => 'array',
            'duplicate_candidate_ids' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<SyncLog, $this>
     */
    public function syncLog(): BelongsTo
    {
        return $this->belongsTo(SyncLog::class);
    }
}
