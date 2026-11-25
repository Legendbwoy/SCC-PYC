<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

/**
 * Estimates SMS encoding (GSM-7 vs UCS-2) and message segment count (Spec Section 10.10).
 */
class GsmSegmentEstimator
{
    private const GSM7_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const GSM7_EXTENDED = "^{}\\[~]|€\f";

    /**
     * @return array{encoding: string, units: int, segments: int, per_segment_limit: int}
     */
    public function estimate(string $text): array
    {
        if ($text === '') {
            return [
                'encoding' => 'GSM-7',
                'units' => 0,
                'segments' => 0,
                'per_segment_limit' => 160,
            ];
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $isGsm7 = true;
        $septets = 0;

        foreach ($chars as $char) {
            if (mb_strpos(self::GSM7_BASIC, $char) !== false) {
                $septets += 1;
            } elseif (mb_strpos(self::GSM7_EXTENDED, $char) !== false) {
                $septets += 2;
            } else {
                $isGsm7 = false;
                break;
            }
        }

        if ($isGsm7) {
            $segments = $septets <= 160 ? 1 : (int) ceil($septets / 153);

            return [
                'encoding' => 'GSM-7',
                'units' => $septets,
                'segments' => $segments,
                'per_segment_limit' => $septets <= 160 ? 160 : 153,
            ];
        }

        $codeUnits = mb_strlen($text, 'UTF-8');
        $segments = $codeUnits <= 70 ? 1 : (int) ceil($codeUnits / 67);

        return [
            'encoding' => 'UCS-2',
            'units' => $codeUnits,
            'segments' => $segments,
            'per_segment_limit' => $codeUnits <= 70 ? 70 : 67,
        ];
    }
}
