<?php

declare(strict_types=1);

namespace App\Modules\Membership\Services;

use App\Modules\Notifications\Services\RecipientResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Normalizes and validates Google Form or manual membership payloads (Spec Section 5.2 & 5.4).
 */
class MemberValidationService
{
    public function __construct(
        private readonly RecipientResolver $recipientResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $rawPayload
     * @param  array<string, list<string>|string>  $customMapping
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $rawPayload, array $customMapping = []): array
    {
        /** @var array<string, list<string>|string> $defaultMapping */
        $defaultMapping = config('pyc.membership.google_sheets.default_field_mapping', []);
        $mapping = array_replace($defaultMapping, $customMapping);

        $firstName = $this->extractMappedValue($rawPayload, $mapping['first_name'] ?? ['first_name']);
        $middleName = $this->extractMappedValue($rawPayload, $mapping['middle_name'] ?? ['middle_name']);
        $lastName = $this->extractMappedValue($rawPayload, $mapping['last_name'] ?? ['last_name']);
        $fullName = $this->extractMappedValue($rawPayload, $mapping['full_name'] ?? ['full_name']);

        if (($firstName === null || $lastName === null) && $fullName !== null) {
            [$parsedFirst, $parsedMiddle, $parsedLast] = $this->splitFullName($fullName);
            $firstName = $firstName ?: $parsedFirst;
            $middleName = $middleName ?: $parsedMiddle;
            $lastName = $lastName ?: $parsedLast;
        }

        $rawPhone = $this->extractMappedValue($rawPayload, $mapping['phone'] ?? ['phone']);
        $rawEmail = $this->extractMappedValue($rawPayload, $mapping['email'] ?? ['email']);
        $rawEmergencyPhone = $this->extractMappedValue($rawPayload, $mapping['emergency_contact_phone'] ?? ['emergency_contact_phone']);

        $normalizedPhone = $this->recipientResolver->normalizePhone($rawPhone);
        $normalizedEmail = $this->recipientResolver->normalizeEmail($rawEmail);
        $normalizedEmergencyPhone = $this->recipientResolver->normalizePhone($rawEmergencyPhone) ?? $rawEmergencyPhone;

        $normalized = [
            'first_name' => $firstName ? $this->titleClean($firstName) : null,
            'middle_name' => $middleName ? $this->titleClean($middleName) : null,
            'last_name' => $lastName ? $this->titleClean($lastName) : null,
            'phone' => $normalizedPhone,
            'email' => $normalizedEmail,
            'date_of_birth' => $this->parseDate($this->extractMappedValue($rawPayload, $mapping['date_of_birth'] ?? ['date_of_birth'])),
            'gender' => $this->normalizeGender($this->extractMappedValue($rawPayload, $mapping['gender'] ?? ['gender'])),
            'address' => $this->extractMappedValue($rawPayload, $mapping['address'] ?? ['address']),
            'occupation' => $this->extractMappedValue($rawPayload, $mapping['occupation'] ?? ['occupation']),
            'group_name' => $this->extractMappedValue($rawPayload, $mapping['group_name'] ?? ['group_name']),
            'emergency_contact_name' => $this->extractMappedValue($rawPayload, $mapping['emergency_contact_name'] ?? ['emergency_contact_name']),
            'emergency_contact_phone' => $normalizedEmergencyPhone,
        ];

        $validator = Validator::make($normalized, [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:190', 'required_without:phone'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:24'],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'group_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
        ], [
            'phone.required_without' => 'Either a valid phone number or email address is required.',
            'email.required_without' => 'Either a valid email address or phone number is required.',
        ]);

        if ($rawPhone !== null && $rawPhone !== '' && $normalizedPhone === null) {
            $validator->after(static function ($v) use ($rawPhone): void {
                $v->errors()->add('phone', "Phone number [{$rawPhone}] could not be normalized to international format.");
            });
        }

        if ($rawEmail !== null && $rawEmail !== '' && $normalizedEmail === null) {
            $validator->after(static function ($v) use ($rawEmail): void {
                $v->errors()->add('email', "Email address [{$rawEmail}] is invalid.");
            });
        }

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>|string  $candidates
     */
    private function extractMappedValue(array $payload, array|string $candidates): ?string
    {
        $keys = is_array($candidates) ? $candidates : [$candidates];

        // Case-insensitive lookup across payload keys
        $lowerMap = [];
        foreach ($payload as $k => $v) {
            $lowerMap[strtolower(trim((string) $k))] = $v;
        }

        foreach ($keys as $candidate) {
            $lookup = strtolower(trim($candidate));
            if (array_key_exists($lookup, $lowerMap)) {
                $val = trim((string) $lowerMap[$lookup]);
                if ($val !== '') {
                    return $val;
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) === 0) {
            return [null, null, null];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, $parts[0]];
        }

        if (count($parts) === 2) {
            return [$parts[0], null, $parts[1]];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);
        $middle = implode(' ', $parts);

        return [$first, $middle !== '' ? $middle : null, $last];
    }

    private function titleClean(string $name): string
    {
        return preg_replace('/\s+/', ' ', trim($name)) ?? trim($name);
    }

    private function parseDate(?string $rawDate): ?string
    {
        if ($rawDate === null || trim($rawDate) === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'] as $format) {
            try {
                $dt = Carbon::createFromFormat($format, trim($rawDate));
                if ($dt !== false) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Continue trying next format
            }
        }

        try {
            return Carbon::parse($rawDate)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeGender(?string $gender): ?string
    {
        if ($gender === null || trim($gender) === '') {
            return null;
        }

        return match (strtolower(trim($gender))) {
            'm', 'male' => 'male',
            'f', 'female' => 'female',
            default => strtolower(trim($gender)),
        };
    }
}
