<?php

declare(strict_types=1);

namespace App\Support\Shared;

use InvalidArgumentException;

/**
 * Immutable monetary value object storing integer pesewas only.
 * Enforces Spec Section 3.5 & 8.1: never use floating-point values for money.
 */
final readonly class Money
{
    public function __construct(
        public int $pesewas,
        public string $currency = 'GHS',
    ) {
        if ($currency === '') {
            throw new InvalidArgumentException('Currency code cannot be empty.');
        }
    }

    public static function fromPesewas(int $pesewas, string $currency = 'GHS'): self
    {
        return new self($pesewas, strtoupper($currency));
    }

    /**
     * Safely parses a decimal string like "10", "10.00", or "10.5" into integer pesewas without float math.
     */
    public static function fromMajorString(string $amount, string $currency = 'GHS'): self
    {
        $trimmed = trim($amount);

        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $trimmed, $matches)) {
            throw new InvalidArgumentException("Invalid monetary amount format: [{$amount}].");
        }

        $sign = $matches[1] === '-' ? -1 : 1;
        $major = (int) $matches[2];
        $minor = isset($matches[3]) ? (int) str_pad($matches[3], 2, '0', STR_PAD_RIGHT) : 0;

        return new self($sign * (($major * 100) + $minor), strtoupper($currency));
    }

    public static function zero(string $currency = 'GHS'): self
    {
        return new self(0, strtoupper($currency));
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->pesewas + $other->pesewas, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->pesewas - $other->pesewas, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->pesewas === $other->pesewas;
    }

    public function isPositive(): bool
    {
        return $this->pesewas > 0;
    }

    public function isNegative(): bool
    {
        return $this->pesewas < 0;
    }

    public function isZero(): bool
    {
        return $this->pesewas === 0;
    }

    /**
     * Returns e.g. "10.00" using integer division/modulo only.
     */
    public function formattedAmount(): string
    {
        $sign = $this->pesewas < 0 ? '-' : '';
        $abs = abs($this->pesewas);
        $major = intdiv($abs, 100);
        $minor = $abs % 100;

        return sprintf('%s%s.%02d', $sign, number_format($major), $minor);
    }

    /**
     * Returns e.g. "GHS 10.00".
     */
    public function format(): string
    {
        return sprintf('%s %s', $this->currency, $this->formattedAmount());
    }

    /**
     * Matches Spec Section 13.8 API serialization convention.
     *
     * @return array{amount_pesewas: int, currency: string, formatted_amount: string}
     */
    public function toApiArray(): array
    {
        return [
            'amount_pesewas' => $this->pesewas,
            'currency' => $this->currency,
            'formatted_amount' => $this->formattedAmount(),
        ];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: cannot combine [{$this->currency}] and [{$other->currency}]."
            );
        }
    }
}
