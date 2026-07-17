<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Property\GenericProperty;
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

    #[\Override]
    abstract public function getName(): string;

    #[\Override]
    public function addProperty(PropertyInterface $property): void
    {
        $this->properties[] = $property;
    }

    #[\Override]
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

    #[\Override]
    public function getProperties(): array
    {
        return $this->properties;
    }

    #[\Override]
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
    #[\Override]
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

    #[\Override]
    public function addComponent(ComponentInterface $component): void
    {
        $component->setParent($this);
        $this->components[] = $component;
    }

    #[\Override]
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

    #[\Override]
    public function removeComponent(ComponentInterface $component): void
    {
        $key = array_search($component, $this->components, true);
        if ($key !== false) {
            unset($this->components[$key]);
            $component->setParent(null);
        }
    }

    #[\Override]
    public function getParent(): ?ComponentInterface
    {
        return $this->parent;
    }

    #[\Override]
    public function setParent(?ComponentInterface $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
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

    /**
     * Set a date/date-time property, accepting a string or a DateTimeInterface
     *
     * Shared by every DTSTART/DTEND/DTSTAMP/DUE/COMPLETED-style setter so they
     * stay consistent. A string is stored verbatim (unvalidated -- the historic
     * behaviour); a DateTimeInterface is formatted per $params:
     *
     *   - VALUE=DATE          -> "Ymd"
     *   - TZID present        -> "Ymd\THis" (local wall-clock; the TZID names the
     *                            zone, so no trailing Z)
     *   - otherwise           -> converted to UTC, "Ymd\THis" with a trailing Z
     *
     * Every entry in $params is attached to the property as a parameter, which
     * is the only way to reach TZID/VALUE through the setters.
     *
     * @param array<string, string> $params
     */
    protected function setDateProperty(string $name, string|\DateTimeInterface $value, array $params = []): void
    {
        $this->removeProperty($name);

        if ($value instanceof \DateTimeInterface) {
            $value = $this->formatDateValue($value, $params);
        }

        $property = GenericProperty::create($name, $value);
        foreach ($params as $paramName => $paramValue) {
            $property->setParameter($paramName, $paramValue);
        }

        $this->addProperty($property);
    }

    /**
     * Format a DateTimeInterface for a date/date-time property value
     *
     * @param array<string, string> $params
     */
    private function formatDateValue(\DateTimeInterface $value, array $params): string
    {
        $valueType = null;
        $hasTzid = false;
        foreach ($params as $paramName => $paramValue) {
            if (strcasecmp($paramName, 'VALUE') === 0) {
                $valueType = strtoupper($paramValue);
            } elseif (strcasecmp($paramName, 'TZID') === 0) {
                $hasTzid = true;
            }
        }

        if ($valueType === 'DATE') {
            return $value->format('Ymd');
        }

        if ($hasTzid) {
            // The TZID names the zone; the value is local wall-clock, no Z.
            return $value->format('Ymd\THis');
        }

        // No zone given: an absolute instant is unambiguous only as UTC.
        return \DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Ymd\THis') . 'Z';
    }

    /**
     * Validate this component and its sub-components against RFC 5545
     *
     * Template method: runs this component's own checks via validateSelf(), then
     * descends into every child. Concrete components override validateSelf(), not
     * this, so the recursion cannot be forgotten or bypassed -- which is why it is
     * final. It is deliberately fail-fast: the first violation in document order
     * (self before children) is thrown.
     *
     * To collect every error in one pass instead of stopping at the first, use
     * {@see \Icalendar\Validation\Validator::validate()}.
     *
     * @throws \Icalendar\Exception\ValidationException if the component or any
     *   descendant is invalid
     */
    #[\Override]
    final public function validate(): void
    {
        $this->validateSelf();

        foreach ($this->getComponents() as $component) {
            $component->validate();
        }
    }

    /**
     * Validate this component's own RFC 5545 requirements, ignoring children
     *
     * No-op by default: a component with no rules of its own -- GenericComponent,
     * and any X- extension -- is valid. Concrete components override this with
     * their required-property and consistency checks. validate() supplies the
     * recursion around it.
     *
     * @throws \Icalendar\Exception\ValidationException if the component is invalid
     */
    protected function validateSelf(): void
    {
    }
}
