<?php

declare(strict_types=1);

namespace App\Modules\Membership\Models;

use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'member_number',
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'address',
        'occupation',
        'emergency_contact_name',
        'emergency_contact_phone',
        'joined_at',
        'approved_at',
        'status',
        'is_duplicate_flagged',
        'duplicate_candidate_ids',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'approved_at' => 'datetime',
            'is_duplicate_flagged' => 'boolean',
            'duplicate_candidate_ids' => 'array',
        ];
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));
    }

    /**
     * @return HasMany<MemberSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(MemberSubmission::class);
    }

    /**
     * @return HasMany<MemberApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(MemberApproval::class)->latest('id');
    }

    /**
     * @return HasOne<MemberAccount, $this>
     */
    public function account(): HasOne
    {
        return $this->hasOne(MemberAccount::class);
    }

    /**
     * @return HasMany<MemberInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(MemberInvitation::class)->latest('id');
    }

    /**
     * @return HasMany<MembershipStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(MembershipStatusHistory::class)->latest('id');
    }

    /**
     * @return BelongsToMany<MemberGroup, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(MemberGroup::class, 'member_group_assignments')
            ->withPivot(['role_title', 'assigned_at', 'ended_at'])
            ->wherePivotNull('ended_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<MemberObligation, $this>
     */
    public function obligations(): HasMany
    {
        return $this->hasMany(MemberObligation::class)->orderBy('period_key');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class)->latest('id');
    }
}
