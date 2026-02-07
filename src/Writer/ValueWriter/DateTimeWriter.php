<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use DateTimeInterface;

/**
 * Writer for DATE-TIME values (YYYYMMDDTHHMMSS[Z] format)
 */
class DateTimeWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('DateTimeWriter expects DateTimeInterface or string, got ' . gettype($value));
        }

        // Check if UTC
        $timezone = $value->getTimezone();
        $tzName = $timezone->getName();
        $isUtc = in_array($tzName, ['UTC', '+00:00', 'GMT', 'Etc/UTC', 'Z'], true)
            || ($value->getOffset() === 0 && !preg_match('/^[A-Z][a-z]/', $tzName));
        if ($isUtc) {
            return $value->format('Ymd\THis') . 'Z';
        }

        // Local time (no Z suffix, TZID handled by property)
        return $value->format('Ymd\THis');
    }

    public function getType(): string
    {
        return 'DATE-TIME';
    }

    public function canWrite(mixed $value): bool
    {
        return $value instanceof DateTimeInterface || is_string($value);
    }
}