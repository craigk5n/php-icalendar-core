<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * DATE-TIME value implementation
 */
class DateTimeValue extends AbstractValue
{
    public function __construct(
        private readonly \DateTimeInterface $value,
        private readonly string $type = 'DATE-TIME'
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRawValue(): mixed
    {
        return $this->value;
    }

    public function getValue(): \DateTimeInterface
    {
        return $this->value;
    }

    public function serialize(): string
    {
        return $this->value->format('Ymd\THis');
    }

    public function isDefault(): bool
    {
        return $this->type === 'DATE-TIME';
    }
}