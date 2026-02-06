<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for UTC-OFFSET values ([+/-]HHMM[SS] format)
 */
class UtcOffsetWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException('UtcOffsetWriter expects int (seconds), got ' . gettype($value));
        }

        return $this->formatOffset($value);
    }

    /**
     * Format offset in seconds to UTC-OFFSET format
     */
    private function formatOffset(int $offsetSeconds): string
    {
        $sign = $offsetSeconds < 0 ? '-' : '+';
        $offsetSeconds = abs($offsetSeconds);

        $hours = (int) ($offsetSeconds / 3600);
        $minutes = (int) (($offsetSeconds % 3600) / 60);
        $seconds = $offsetSeconds % 60;

        if ($seconds > 0) {
            return sprintf('%s%02d%02d%02d', $sign, $hours, $minutes, $seconds);
        }

        return sprintf('%s%02d%02d', $sign, $hours, $minutes);
    }

    public function getType(): string
    {
        return 'UTC-OFFSET';
    }

    public function canWrite(mixed $value): bool
    {
        return is_int($value);
    }
}