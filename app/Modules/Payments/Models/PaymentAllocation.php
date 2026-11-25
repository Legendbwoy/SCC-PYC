<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Modules\Contributions\Models\MemberObligation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'payment_id',
        'member_obligation_id',
        'amount_pesewas',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_pesewas' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<MemberObligation, $this>
     */
    public function obligation(): BelongsTo
    {
        return $this->belongsTo(MemberObligation::class, 'member_obligation_id');
    }
}
