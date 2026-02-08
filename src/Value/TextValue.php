<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * Simple string value implementation
 */
class TextValue implements ValueInterface
{
    public function __construct(private string $value) {}

    #[\Override]
    public function getType(): string
    {
        return 'TEXT';
    }

    #[\Override]
    public function getRawValue(): string
    {
        return $this->value;
    }

    #[\Override]
    public function serialize(): string
    {
        return $this->value;
    }

    #[\Override]
    public function isDefault(): bool
    {
        return true;
    }
}
