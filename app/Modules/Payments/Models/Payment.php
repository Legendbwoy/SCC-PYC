<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Models\User;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Membership\Models\Member;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use App\Support\Shared\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'reference',
        'member_id',
        'amount_pesewas',
        'currency',
        'method',
        'payment_status',
        'approval_status',
        'provider_reference',
        'provider_authorization_url',
        'requested_allocations',
        'recorded_by',
        'approved_by',
        'approved_at',
        'verified_at',
        'posted_at',
        'journal_entry_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_pesewas' => 'integer',
            'requested_allocations' => 'array',
            'approved_at' => 'datetime',
            'verified_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(static function (): never {
            throw new ImmutableRecordException('Financial payment records cannot be physically deleted.');
        });
    }

    public function amountMoney(): Money
    {
        return Money::fromPesewas($this->amount_pesewas, $this->currency);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * @return HasOne<Receipt, $this>
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
