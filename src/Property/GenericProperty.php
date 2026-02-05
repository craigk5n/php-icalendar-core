<?php

declare(strict_types=1);

namespace Icalendar\Property;

use Icalendar\Value\TextValue;
use Icalendar\Value\ValueInterface;

/**
 * Generic property implementation for simple string values
 */
class GenericProperty implements PropertyInterface
{
    public function __construct(
        private string $name,
        private ValueInterface $value,
        private array $parameters = []
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): ValueInterface
    {
        return $this->value;
    }

    public function setValue(ValueInterface $value): void
    {
        $this->value = $value;
    }

    public function getParameter(string $name): ?string
    {
        return $this->parameters[$name] ?? null;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameter(string $name, string $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function removeParameter(string $name): void
    {
        unset($this->parameters[$name]);
    }

    public static function create(string $name, string $value): self
    {
        return new self($name, new TextValue($value));
    }
}
