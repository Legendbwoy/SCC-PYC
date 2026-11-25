<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Modules\Membership\Models\Member;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use App\Support\Shared\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable official PYC receipt issued once after verification, allocation, and journal posting (Spec Section 7.3).
 */
class Receipt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'receipt_number',
        'payment_id',
        'member_id',
        'amount_pesewas',
        'currency',
        'issued_at',
        'snapshot',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_pesewas' => 'integer',
            'issued_at' => 'datetime',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new ImmutableRecordException('Issued official receipts are immutable.');
        });

        static::deleting(static function (): never {
            throw new ImmutableRecordException('Issued official receipts cannot be deleted.');
        });
    }

    public function amountMoney(): Money
    {
        return Money::fromPesewas($this->amount_pesewas, $this->currency);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
