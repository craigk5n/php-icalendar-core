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

    #[\Override]
    abstract public function getName(): string;

    #[\Override]
    public function getValue(): ValueInterface
    {
        return $this->value;
    }

    #[\Override]
    public function setValue(ValueInterface $value): void
    {
        $this->value = $value;
    }

    #[\Override]
    public function getParameter(string $name): ?string
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function getParameters(): array
    {
        return $this->parameters;
    }

    #[\Override]
    public function setParameter(string $name, string $value): void
    {
        $this->parameters[$name] = $value;
    }

    #[\Override]
    public function removeParameter(string $name): void
    {
        unset($this->parameters[$name]);
    }
}
