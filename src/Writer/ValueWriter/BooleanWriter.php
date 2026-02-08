<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for BOOLEAN values
 */
class BooleanWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (!is_bool($value)) {
            throw new \InvalidArgumentException('BooleanWriter expects bool, got ' . gettype($value));
        }

        return $value ? 'TRUE' : 'FALSE';
    }

    #[\Override]
    public function getType(): string
    {
        return 'BOOLEAN';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_bool($value);
    }
}