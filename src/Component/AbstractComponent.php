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
        $this->properties[] = $property;
    }

    public function getProperty(string $name): ?PropertyInterface
    {
        // Iterate backwards to return the most recently added match (last-write-wins)
        for ($i = count($this->properties) - 1; $i >= 0; $i--) {
            if ($this->properties[$i]->getName() === $name) {
                return $this->properties[$i];
            }
        }
        return null;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function removeProperty(string $name): void
    {
        $this->properties = array_values(array_filter(
            $this->properties,
            fn(PropertyInterface $p) => $p->getName() !== $name
        ));
    }

    /**
     * Get all properties with a given name, or all properties if no name specified
     *
     * @return PropertyInterface[]
     */
    public function getAllProperties(?string $name = null): array
    {
        if ($name === null) {
            return $this->properties;
        }
        return array_values(array_filter(
            $this->properties,
            fn(PropertyInterface $p) => $p->getName() === $name
        ));
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

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $name = strtolower($this->getName());
        $properties = [];
        foreach ($this->getProperties() as $property) {
            $propName = strtolower($property->getName());
            $params = (object) array_change_key_case($property->getParameters(), CASE_LOWER);
            $type = strtolower($property->getValue()->getType());
            $value = $property->getValue()->getRawValue();
            
            // Basic handling for multi-valued properties (simplified for now)
            if (str_contains($value, ',') && !in_array($propName, ['summary', 'description', 'location'])) {
                 // Should ideally split by comma if not escaped, but keeping it simple for now
            }

            $properties[] = [$propName, $params, $type, $value];
        }

        $components = [];
        foreach ($this->getComponents() as $component) {
            $components[] = $component->toArray();
        }

        return [$name, $properties, $components];
    }
}
