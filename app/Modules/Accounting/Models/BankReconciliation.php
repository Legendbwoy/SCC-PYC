<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliation extends Model
{
    protected $fillable = [
        'financial_account_id',
        'statement_date',
        'statement_balance_pesewas',
        'ledger_balance_pesewas',
        'status',
        'notes',
        'reconciled_by',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'statement_balance_pesewas' => 'integer',
            'ledger_balance_pesewas' => 'integer',
            'reconciled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }
}
