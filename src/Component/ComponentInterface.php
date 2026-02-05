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
}
