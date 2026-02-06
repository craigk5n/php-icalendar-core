<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use DateTimeInterface;

/**
 * Writer for DATE values (YYYYMMDD format)
 */
class DateWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!$value instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('DateWriter expects DateTimeInterface, got ' . gettype($value));
        }

        return $value->format('Ymd');
    }

    public function getType(): string
    {
        return 'DATE';
    }

    public function canWrite(mixed $value): bool
    {
        return $value instanceof DateTimeInterface;
    }
}