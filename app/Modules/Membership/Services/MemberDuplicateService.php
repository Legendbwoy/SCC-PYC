<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Modules\Membership\Models\Member;
use Illuminate\Database\Eloquent\Collection;

/**
 * Flags possible duplicate members for human review rather than silently merging (Spec Section 5.2 & 5.4).
 */
class MemberDuplicateService
{
    /**
     * @param  array<string, mixed>  $normalizedData
     * @return Collection<int, Member>
     */
    public function findMatches(array $normalizedData, ?int $excludeMemberId = null): Collection
    {
        $phone = $normalizedData['phone'] ?? null;
        $email = $normalizedData['email'] ?? null;
        $firstName = $normalizedData['first_name'] ?? null;
        $lastName = $normalizedData['last_name'] ?? null;
        $dob = $normalizedData['date_of_birth'] ?? null;

        if (empty($phone) && empty($email) && (empty($firstName) || empty($lastName))) {
            return new Collection;
        }

        $query = Member::query();

        if ($excludeMemberId !== null) {
            $query->whereKeyNot($excludeMemberId);
        }

        $query->where(static function ($builder) use ($phone, $email, $firstName, $lastName, $dob): void {
            if (! empty($phone)) {
                $builder->orWhere('phone', $phone);
            }

            if (! empty($email)) {
                $builder->orWhere('email', strtolower((string) $email));
            }

            if (! empty($firstName) && ! empty($lastName)) {
                $builder->orWhere(static function ($nameQuery) use ($firstName, $lastName, $dob): void {
                    $nameQuery->whereRaw('LOWER(first_name) = ?', [strtolower((string) $firstName)])
                        ->whereRaw('LOWER(last_name) = ?', [strtolower((string) $lastName)]);

                    if (! empty($dob)) {
                        $nameQuery->whereDate('date_of_birth', $dob);
                    }
                });
            }
        });

        return $query->limit(10)->get();
    }
}
