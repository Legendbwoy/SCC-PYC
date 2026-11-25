<?php

declare(strict_types=1);

namespace App\Modules\Notifications\DTOs;

final readonly class SmsResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public bool $accepted,
        public string $status,
        public ?string $providerReference = null,
        public ?string $errorMessage = null,
        public bool $retryable = false,
        public array $rawResponse = [],
        public ?int $costPesewas = null,
        public ?string $costCurrency = null,
        public bool $isAuthFailure = false,
        public bool $isAmbiguousTimeout = false,
    ) {}
}
