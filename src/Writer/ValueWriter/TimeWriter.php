<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use DateTimeInterface;

/**
 * Writer for TIME values (HHMMSS[Z] format)
 */
class TimeWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('TimeWriter expects DateTimeInterface or string, got ' . gettype($value));
        }

        // Check if UTC
        $timezone = $value->getTimezone();
        if ($timezone->getName() === 'UTC' || $timezone->getName() === '+00:00' || $timezone->getName() === 'Z') {
            return $value->format('His') . 'Z';
        }

        // Local time
        return $value->format('His');
    }

    #[\Override]
    public function getType(): string
    {
        return 'TIME';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return $value instanceof DateTimeInterface || is_string($value);
    }
}
