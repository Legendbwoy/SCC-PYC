<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Modules\Notifications\Services\GsmSegmentEstimator;
use PHPUnit\Framework\TestCase;

class GsmSegmentEstimatorTest extends TestCase
{
    public function test_it_estimates_single_and_multipart_gsm7_and_unicode_segments(): void
    {
        $estimator = new GsmSegmentEstimator;

        $short = $estimator->estimate('Payment confirmed! GHS 10.00 received for Monthly Dues.');
        $this->assertSame('GSM-7', $short['encoding']);
        $this->assertSame(1, $short['segments']);

        $longGsm = $estimator->estimate(str_repeat('A', 165));
        $this->assertSame('GSM-7', $longGsm['encoding']);
        $this->assertSame(2, $longGsm['segments']);

        // Extended GSM-7 character (€ counts as 2 septets)
        $extended = $estimator->estimate(str_repeat('€', 81)); // 162 septets -> 2 segments
        $this->assertSame('GSM-7', $extended['encoding']);
        $this->assertSame(162, $extended['units']);
        $this->assertSame(2, $extended['segments']);

        // Non-GSM emoji triggers UCS-2 encoding (70 single / 67 multipart)
        $unicode = $estimator->estimate('Welcome to PYC 🙏 '.str_repeat('B', 60));
        $this->assertSame('UCS-2', $unicode['encoding']);
        $this->assertSame(2, $unicode['segments']);
    }
}
