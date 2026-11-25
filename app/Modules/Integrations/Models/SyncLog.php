<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Models\User;
use App\Modules\Membership\Models\MemberSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncLog extends Model
{
    protected $fillable = [
        'provider',
        'trigger_type',
        'status',
        'fetched_count',
        'imported_count',
        'skipped_count',
        'duplicate_flagged_count',
        'failed_count',
        'error_summary',
        'error_details',
        'started_at',
        'finished_at',
        'initiated_by',
    ];

    protected function casts(): array
    {
        return [
            'fetched_count' => 'integer',
            'imported_count' => 'integer',
            'skipped_count' => 'integer',
            'duplicate_flagged_count' => 'integer',
            'failed_count' => 'integer',
            'error_details' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * @return HasMany<MemberSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(MemberSubmission::class, 'sync_log_id');
    }
}
