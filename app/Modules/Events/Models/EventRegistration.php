<?php

declare(strict_types=1);

namespace App\Modules\Events\Models;

use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Membership\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'member_id',
        'member_obligation_id',
        'status',
        'registered_at',
        'cancelled_at',
        'cancellation_reason',
        'attended_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PycEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(PycEvent::class, 'event_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<MemberObligation, $this>
     */
    public function obligation(): BelongsTo
    {
        return $this->belongsTo(MemberObligation::class, 'member_obligation_id');
    }
}
