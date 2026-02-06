<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Interface for value writers that serialize PHP values to iCalendar format
 */
interface ValueWriterInterface
{
    /**
     * Write a PHP value to iCalendar format
     *
     * @param mixed $value The value to write
     * @return string The serialized iCalendar value
     */
    public function write(mixed $value): string;

    /**
     * Get the data type name this writer handles
     */
    public function getType(): string;

    /**
     * Check if this writer can handle the given value
     *
     * @param mixed $value
     * @return bool
     */
    public function canWrite(mixed $value): bool;
}