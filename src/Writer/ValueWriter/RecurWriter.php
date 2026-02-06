<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use Icalendar\Recurrence\RRule;

/**
 * Writer for RECUR (RRULE) values
 */
class RecurWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!$value instanceof RRule) {
            throw new \InvalidArgumentException('RecurWriter expects RRule, got ' . gettype($value));
        }

        return $value->toString();
    }

    public function getType(): string
    {
        return 'RECUR';
    }

    public function canWrite(mixed $value): bool
    {
        return $value instanceof RRule;
    }
}