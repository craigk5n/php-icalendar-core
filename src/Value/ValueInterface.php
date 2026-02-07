<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * Interface for all iCalendar value types
 */
interface ValueInterface
{
    /**
     * Get the value type name (e.g., TEXT, DATE-TIME)
     */
    public function getType(): string;

    /**
     * Get the raw value as a string
     */
    public function getRawValue(): string;

    /**
     * Serialize to iCalendar format
     */
    public function serialize(): string;

    /**
     * Check if this value type is the default for a property
     */
    public function isDefault(): bool;
}
