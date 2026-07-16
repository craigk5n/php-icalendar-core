<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Property\PropertyInterface;

/**
 * Interface for all iCalendar components
 */
interface ComponentInterface
{
    /**
     * Get the component name (e.g., VEVENT, VTODO)
     */
    public function getName(): string;

    /**
     * Add a property to the component
     */
    public function addProperty(PropertyInterface $property): void;

    /**
     * Get a property by name
     */
    public function getProperty(string $name): ?PropertyInterface;

    /**
     * Get all properties
     *
     * @return PropertyInterface[]
     */
    public function getProperties(): array;

    /**
     * Get all properties by name
     *
     * @return PropertyInterface[]
     */
    public function getAllProperties(?string $name = null): array;

    /**
     * Remove a property by name
     */
    public function removeProperty(string $name): void;

    /**
     * Add a sub-component
     */
    public function addComponent(ComponentInterface $component): void;

    /**
     * Get sub-components by type
     *
     * @return ComponentInterface[]
     */
    public function getComponents(?string $type = null): array;

    /**
     * Remove a sub-component
     */
    public function removeComponent(ComponentInterface $component): void;

    /**
     * Get parent component
     */
    public function getParent(): ?ComponentInterface;

    /**
     * Set parent component
     */
    public function setParent(?ComponentInterface $parent): void;

    /**
     * Convert component to jCal array format (RFC 7265)
     *
     * @return array<mixed>
     */
    public function toArray(): array;

    /**
     * Validate this component against its RFC 5545 requirements
     *
     * Fail-fast and shallow: throws on the first violation of *this* component's
     * own rules, and does not descend into sub-components. For a full tree walk
     * that collects every error rather than stopping at the first, use
     * {@see \Icalendar\Validation\Validator::validate()}.
     *
     * AbstractComponent supplies a no-op default, so a component with no rules
     * of its own (GenericComponent, and any X- extension) is valid by default.
     *
     * @throws \Icalendar\Exception\ValidationException if the component is invalid
     */
    public function validate(): void;
}
