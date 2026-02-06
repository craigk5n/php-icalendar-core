<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for INTEGER values
 */
class IntegerWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!is_int($value) && !(is_float($value) && floor($value) === $value)) {
            throw new \InvalidArgumentException('IntegerWriter expects int, got ' . gettype($value));
        }

        return (string) (int) $value;
    }

    public function getType(): string
    {
        return 'INTEGER';
    }

    public function canWrite(mixed $value): bool
    {
        return is_int($value) || (is_float($value) && floor($value) === $value);
    }
}