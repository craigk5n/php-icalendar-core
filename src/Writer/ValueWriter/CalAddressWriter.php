<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for CAL-ADDRESS values (mailto: URI)
 */
class CalAddressWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('CalAddressWriter expects string, got ' . gettype($value));
        }

        // Normalize mailto: prefix to lowercase
        $value = preg_replace('/^mailto:/i', 'mailto:', $value);

        // Ensure it starts with mailto:
        if (!str_starts_with($value, 'mailto:')) {
            $value = 'mailto:' . $value;
        }

        return $value;
    }

    public function getType(): string
    {
        return 'CAL-ADDRESS';
    }

    public function canWrite(mixed $value): bool
    {
        return is_string($value);
    }
}