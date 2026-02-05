<?php

declare(strict_types=1);

namespace Icalendar\Property;

use Icalendar\Value\ValueInterface;

/**
 * Abstract base class for all properties
 */
abstract class AbstractProperty implements PropertyInterface
{
    protected ValueInterface $value;

    /** @var array<string, string> */
    protected array $parameters = [];

    abstract public function getName(): string;

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
}
