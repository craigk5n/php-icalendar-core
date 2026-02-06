<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for URI values
 */
class UriWriter implements ValueWriterInterface
{
    public function write(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('UriWriter expects string, got ' . gettype($value));
        }

        return $value;
    }

    public function getType(): string
    {
        return 'URI';
    }

    public function canWrite(mixed $value): bool
    {
        return is_string($value);
    }
}