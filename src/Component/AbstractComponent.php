<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Property\PropertyInterface;

/**
 * Abstract base class for all components
 */
abstract class AbstractComponent implements ComponentInterface
{
    /** @var PropertyInterface[] */
    protected array $properties = [];

    /** @var ComponentInterface[] */
    protected array $components = [];

    protected ?ComponentInterface $parent = null;

    abstract public function getName(): string;

    public function addProperty(PropertyInterface $property): void
    {
        $this->properties[$property->getName()] = $property;
    }

    public function getProperty(string $name): ?PropertyInterface
    {
        return $this->properties[$name] ?? null;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function removeProperty(string $name): void
    {
        unset($this->properties[$name]);
    }

    public function addComponent(ComponentInterface $component): void
    {
        $component->setParent($this);
        $this->components[] = $component;
    }

    public function getComponents(?string $type = null): array
    {
        if ($type === null) {
            return $this->components;
        }

        return array_filter(
            $this->components,
            fn (ComponentInterface $c) => $c->getName() === $type
        );
    }

    public function removeComponent(ComponentInterface $component): void
    {
        $key = array_search($component, $this->components, true);
        if ($key !== false) {
            unset($this->components[$key]);
            $component->setParent(null);
        }
    }

    public function getParent(): ?ComponentInterface
    {
        return $this->parent;
    }

    public function setParent(?ComponentInterface $parent): void
    {
        $this->parent = $parent;
    }
}
