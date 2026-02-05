<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * Simple string value implementation
 */
class TextValue implements ValueInterface
{
    public function __construct(private string $value) {}

    public function getType(): string
    {
        return 'TEXT';
    }

    public function getRawValue(): string
    {
        return $this->value;
    }

    public function serialize(): string
    {
        return $this->value;
    }

    public function isDefault(): bool
    {
        return true;
    }
}
