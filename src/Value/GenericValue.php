<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * Generic value implementation for any iCalendar data type
 */
class GenericValue extends AbstractValue
{
    public function __construct(
        private readonly mixed $value,
        private readonly string $type
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRawValue(): string
    {
        if (is_scalar($this->value)) {
            return (string)$this->value;
        }
        
        if (is_array($this->value)) {
            return 'ARRAY'; // Basic fallback for PHPStan, though writer handles arrays.
        }

        return 'COMPLEX';
    }

    /**
     * Serialize to string. 
     * Note: This might not be fully accurate for complex types, 
     * but the main writer uses ValueWriterFactory instead.
     */
    public function serialize(): string
    {
        if (is_scalar($this->value)) {
            return (string)$this->value;
        }
        return 'COMPLEX-VALUE';
    }
}
