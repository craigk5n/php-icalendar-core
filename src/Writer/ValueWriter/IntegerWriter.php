<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for INTEGER values
 */
class IntegerWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_int($value)) {
            throw new \InvalidArgumentException('IntegerWriter expects int or string, got ' . gettype($value));
        }

        return strval($value);
    }

    #[\Override]
    public function getType(): string
    {
        return 'INTEGER';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_int($value) || is_string($value);
    }
}
