<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Support\Shared\ReferenceGenerator;
use App\Support\Shared\ReferenceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_exact_specification_reference_formats_sequentially(): void
    {
        $generator = app(ReferenceGenerator::class);

        $this->assertSame('PYC-2026-0001', $generator->next(ReferenceType::Member, 2026));
        $this->assertSame('PYC-2026-0002', $generator->next(ReferenceType::Member, 2026));
        $this->assertSame('PYC-2027-0001', $generator->next(ReferenceType::Member, 2027));

        $this->assertSame('PYC-PAY-2026-00001', $generator->next(ReferenceType::Payment, 2026));
        $this->assertSame('PYC-RCT-2026-00001', $generator->next(ReferenceType::Receipt, 2026));
        $this->assertSame('PYC-JRN-2026-00001', $generator->next(ReferenceType::Journal, 2026));
        $this->assertSame('PYC-EXP-2026-00001', $generator->next(ReferenceType::Expense, 2026));
        $this->assertSame('PYC-EVT-2026-00001', $generator->next(ReferenceType::Event, 2026));
        $this->assertSame('PYC-REF-2026-00001', $generator->next(ReferenceType::Refund, 2026));
    }
}
