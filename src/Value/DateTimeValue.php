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

    #[\Override]
    public function getType(): string
    {
        return $this->type;
    }

    #[\Override]
    public function getRawValue(): string
    {
        return $this->serialize();
    }

    public function getValue(): \DateTimeInterface
    {
        return $this->value;
    }

    #[\Override]
    public function serialize(): string
    {
        if ($this->type === 'DATE') {
            return $this->value->format('Ymd');
        }

        $formatted = $this->value->format('Ymd\THis');
        $tz = $this->value->getTimezone();
        if ($tz instanceof \DateTimeZone && ($tz->getName() === 'UTC' || $tz->getName() === 'Z')) {
            $formatted .= 'Z';
        }
        return $formatted;
    }

    #[\Override]
    public function isDefault(): bool
    {
        return $this->type === 'DATE-TIME';
    }
}
