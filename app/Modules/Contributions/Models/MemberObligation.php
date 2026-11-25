<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Models;

use App\Modules\Membership\Models\Member;
use App\Support\Shared\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberObligation extends Model
{
    protected $fillable = [
        'member_id',
        'contribution_campaign_id',
        'period_key',
        'period_start',
        'period_end',
        'due_date',
        'amount_due_pesewas',
        'amount_allocated_pesewas',
        'adjustment_pesewas',
        'currency',
        'status',
        'generated_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'amount_due_pesewas' => 'integer',
            'amount_allocated_pesewas' => 'integer',
            'adjustment_pesewas' => 'integer',
        ];
    }

    /**
     * Effective net requirement after approved waivers/exemptions/adjustments (never negative).
     */
    public function effectiveRequiredPesewas(): int
    {
        return max(0, $this->amount_due_pesewas + $this->adjustment_pesewas);
    }

    /**
     * Remaining unpaid balance in integer pesewas (Spec Section 6.4).
     */
    public function outstandingPesewas(): int
    {
        return max(0, $this->effectiveRequiredPesewas() - $this->amount_allocated_pesewas);
    }

    public function outstandingMoney(): Money
    {
        return Money::fromPesewas($this->outstandingPesewas(), $this->currency);
    }

    public function dueMoney(): Money
    {
        return Money::fromPesewas($this->amount_due_pesewas, $this->currency);
    }

    public function allocatedMoney(): Money
    {
        return Money::fromPesewas($this->amount_allocated_pesewas, $this->currency);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<ContributionCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ContributionCampaign::class, 'contribution_campaign_id');
    }

    /**
     * @return HasMany<ObligationAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(ObligationAdjustment::class)->latest('id');
    }
}
