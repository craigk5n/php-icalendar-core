<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for BOOLEAN values
 */
class BooleanWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!is_bool($value)) {
            throw new \InvalidArgumentException('BooleanWriter expects bool, got ' . gettype($value));
        }

        return $value ? 'TRUE' : 'FALSE';
    }

    public function getType(): string
    {
        return 'BOOLEAN';
    }

    public function canWrite(mixed $value): bool
    {
        return is_bool($value);
    }
}