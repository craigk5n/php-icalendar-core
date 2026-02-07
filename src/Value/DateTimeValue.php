<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * DATE-TIME and DATE value implementation
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

    public function getRawValue(): string
    {
        return $this->serialize();
    }

    public function getValue(): \DateTimeInterface
    {
        return $this->value;
    }

    public function serialize(): string
    {
        if ($this->type === 'DATE') {
            return $this->value->format('Ymd');
        }

        $formatted = $this->value->format('Ymd\THis');
        $tz = $this->value->getTimezone();
        if ($tz !== null && ($tz->getName() === 'UTC' || $tz->getName() === 'Z')) {
            $formatted .= 'Z';
        }
        return $formatted;
    }

    public function isDefault(): bool
    {
        return $this->type === 'DATE-TIME';
    }
}
