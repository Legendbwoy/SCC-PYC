<?php

declare(strict_types=1);

namespace App\Support\Shared;

use App\Support\Shared\Models\ReferenceSequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates sequential references transactionally with row-level locks (Spec Section 7.6).
 *
 * Examples:
 * - Member:  PYC-2026-0001
 * - Payment: PYC-PAY-2026-00001
 * - Receipt: PYC-RCT-2026-00001
 * - Journal: PYC-JRN-2026-00001
 * - Expense: PYC-EXP-2026-00001
 * - Event:   PYC-EVT-2026-00001
 * - Refund:  PYC-REF-2026-00001
 */
class ReferenceGenerator
{
    public function next(ReferenceType $type, ?int $year = null): string
    {
        $resolvedYear = $year ?? (int) Carbon::now((string) config('pyc.organization.timezone', 'Africa/Accra'))->year;
        $prefix = strtoupper((string) config('pyc.organization.reference_prefix', 'PYC'));

        $nextNumber = DB::transaction(function () use ($type, $resolvedYear): int {
            $sequence = ReferenceSequence::query()
                ->where('sequence_type', $type->value)
                ->where('year', $resolvedYear)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                try {
                    $sequence = ReferenceSequence::query()->create([
                        'sequence_type' => $type->value,
                        'year' => $resolvedYear,
                        'last_value' => 1,
                    ]);

                    return 1;
                } catch (QueryException) {
                    $sequence = ReferenceSequence::query()
                        ->where('sequence_type', $type->value)
                        ->where('year', $resolvedYear)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $sequence->last_value += 1;
            $sequence->save();

            return $sequence->last_value;
        });

        $padded = str_pad((string) $nextNumber, $type->paddingWidth(), '0', STR_PAD_LEFT);
        $segment = $type->codeSegment();

        if ($segment === null) {
            return sprintf('%s-%d-%s', $prefix, $resolvedYear, $padded);
        }

        return sprintf('%s-%s-%d-%s', $prefix, $segment, $resolvedYear, $padded);
    }
}
