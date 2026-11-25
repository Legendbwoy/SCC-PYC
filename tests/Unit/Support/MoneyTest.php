<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Shared\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_stores_and_formats_integer_pesewas_accurately(): void
    {
        $dues = Money::fromPesewas(1000, 'GHS');

        $this->assertSame(1000, $dues->pesewas);
        $this->assertSame('GHS', $dues->currency);
        $this->assertSame('10.00', $dues->formattedAmount());
        $this->assertSame('GHS 10.00', $dues->format());
        $this->assertSame([
            'amount_pesewas' => 1000,
            'currency' => 'GHS',
            'formatted_amount' => '10.00',
        ], $dues->toApiArray());
    }

    public function test_it_parses_major_currency_strings_without_floating_point_drift(): void
    {
        $this->assertSame(1000, Money::fromMajorString('10')->pesewas);
        $this->assertSame(1050, Money::fromMajorString('10.5')->pesewas);
        $this->assertSame(1005, Money::fromMajorString('10.05')->pesewas);
        $this->assertSame(1999, Money::fromMajorString('19.99')->pesewas);
    }

    public function test_it_adds_and_subtracts_same_currency_and_blocks_currency_mismatch(): void
    {
        $ten = Money::fromPesewas(1000, 'GHS');
        $fiveFifty = Money::fromPesewas(550, 'GHS');

        $this->assertSame(1550, $ten->add($fiveFifty)->pesewas);
        $this->assertSame(450, $ten->subtract($fiveFifty)->pesewas);

        $this->expectException(InvalidArgumentException::class);
        $ten->add(Money::fromPesewas(100, 'USD'));
    }

    public function test_it_rejects_more_than_two_decimal_places(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromMajorString('10.999');
    }
}
