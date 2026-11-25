<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObligationAdjustment extends Model
{
    protected $fillable = [
        'member_obligation_id',
        'adjustment_type',
        'amount_pesewas',
        'reason',
        'status',
        'approval_reference',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_pesewas' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MemberObligation, $this>
     */
    public function obligation(): BelongsTo
    {
        return $this->belongsTo(MemberObligation::class, 'member_obligation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
