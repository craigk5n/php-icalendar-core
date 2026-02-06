<?php

declare(strict_types=1);

namespace Icalendar\Component\Traits;

/**
 * Shared UTC offset formatting for Standard and Daylight components
 */
trait UtcOffsetFormatterTrait
{
    private function formatUtcOffset(int $offset): string
    {
        $sign = $offset >= 0 ? '+' : '-';
        $abs = abs($offset);
        $hours = intdiv($abs, 3600);
        $minutes = intdiv(($abs % 3600), 60);
        $seconds = $abs % 60;

        $result = sprintf('%s%02d%02d', $sign, $hours, $minutes);
        if ($seconds > 0) {
            $result .= sprintf('%02d', $seconds);
        }

        return $result;
    }
}
