<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Models;

use App\Models\User;
use App\Support\Shared\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionCampaign extends Model
{
    protected $fillable = [
        'contribution_type_id',
        'code',
        'title',
        'description',
        'amount_pesewas',
        'currency',
        'frequency',
        'effective_from',
        'effective_to',
        'due_day_of_month',
        'allow_partial_payments',
        'allow_advance_payments',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_pesewas' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'due_day_of_month' => 'integer',
            'allow_partial_payments' => 'boolean',
            'allow_advance_payments' => 'boolean',
        ];
    }

    public function amountMoney(): Money
    {
        return Money::fromPesewas($this->amount_pesewas, $this->currency);
    }

    /**
     * @return BelongsTo<ContributionType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ContributionType::class, 'contribution_type_id');
    }

    /**
     * @return HasMany<MemberObligation, $this>
     */
    public function obligations(): HasMany
    {
        return $this->hasMany(MemberObligation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
