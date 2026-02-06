<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use DateTimeInterface;

/**
 * Writer for TIME values (HHMMSS[Z] format)
 */
class TimeWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!$value instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('TimeWriter expects DateTimeInterface, got ' . gettype($value));
        }

        // Check if UTC
        $timezone = $value->getTimezone();
        if ($timezone->getName() === 'UTC' || $timezone->getName() === '+00:00') {
            return $value->format('His') . 'Z';
        }

        // Local time
        return $value->format('His');
    }

    public function getType(): string
    {
        return 'TIME';
    }

    public function canWrite(mixed $value): bool
    {
        return $value instanceof DateTimeInterface;
    }
}