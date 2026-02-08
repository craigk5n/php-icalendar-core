<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use Icalendar\Recurrence\RRule;

/**
 * Writer for RECUR (RRULE) values
 */
class RecurWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!$value instanceof RRule) {
            throw new \InvalidArgumentException('RecurWriter expects RRule or string, got ' . gettype($value));
        }

        return $value->toString();
    }

    #[\Override]
    public function getType(): string
    {
        return 'RECUR';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return $value instanceof RRule || is_string($value);
    }
}