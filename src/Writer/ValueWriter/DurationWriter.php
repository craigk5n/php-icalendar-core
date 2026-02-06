<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use DateInterval;

/**
 * Writer for DURATION values (ISO 8601 format)
 *
 * Format: P[n]W or P[n]DT[n]H[n]M[n]S
 */
class DurationWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!$value instanceof DateInterval) {
            throw new \InvalidArgumentException('DurationWriter expects DateInterval, got ' . gettype($value));
        }

        return $this->formatInterval($value);
    }

    /**
     * Format DateInterval to ISO 8601 duration string
     */
    private function formatInterval(DateInterval $interval): string
    {
        // Check if this is a pure week-based duration (divisible by 7)
        if ($interval->d >= 7 && $interval->d % 7 === 0 && $interval->h === 0 && $interval->i === 0 && $interval->s === 0) {
            $weeks = (int) ($interval->d / 7);
            return 'P' . $weeks . 'W';
        }

        $parts = ['P'];

        // Years and months (not typically used in iCalendar but supported)
        if ($interval->y > 0) {
            $parts[] = $interval->y . 'Y';
        }
        if ($interval->m > 0) {
            $parts[] = $interval->m . 'M';
        }

        // Days
        if ($interval->d > 0) {
            $parts[] = $interval->d . 'D';
        }

        // Time component
        $hasTime = $interval->h > 0 || $interval->i > 0 || $interval->s > 0;
        if ($hasTime) {
            $parts[] = 'T';
            if ($interval->h > 0) {
                $parts[] = $interval->h . 'H';
            }
            if ($interval->i > 0) {
                $parts[] = $interval->i . 'M';
            }
            if ($interval->s > 0) {
                $parts[] = $interval->s . 'S';
            }
        }

        $result = implode('', $parts);

        // Handle negative durations
        if ($interval->invert === 1) {
            $result = '-' . $result;
        }

        return $result;
    }

    public function getType(): string
    {
        return 'DURATION';
    }

    public function canWrite(mixed $value): bool
    {
        return $value instanceof DateInterval;
    }
}