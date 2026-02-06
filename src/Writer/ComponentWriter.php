<?php

declare(strict_types=1);

namespace Icalendar\Writer;

use Icalendar\Component\ComponentInterface;
use Icalendar\Property\PropertyInterface;

/**
 * Serializes components to iCalendar format
 *
 * Handles component serialization according to RFC 5545:
 * - Generates BEGIN/END markers for all component types
 * - Serializes properties in the order they were added
 * - Serializes sub-components recursively
 * - Handles empty components correctly
 */
class ComponentWriter
{
    private PropertyWriter $propertyWriter;

    public function __construct(?PropertyWriter $propertyWriter = null)
    {
        $this->propertyWriter = $propertyWriter ?? new PropertyWriter();
    }

    /**
     * Write a component to iCalendar format
     *
     * @param ComponentInterface $component The component to write
     * @return string The serialized component (unfolded)
     */
    public function write(ComponentInterface $component): string
    {
        $lines = [];
        $name = $component->getName();

        // BEGIN marker
        $lines[] = 'BEGIN:' . $name;

        // Serialize all properties in the order they were added
        $properties = $component->getProperties();
        foreach ($properties as $property) {
            $lines[] = $this->propertyWriter->write($property);
        }

        // Serialize sub-components recursively
        $subComponents = $component->getComponents();
        foreach ($subComponents as $subComponent) {
            $lines[] = $this->write($subComponent);
        }

        // END marker
        $lines[] = 'END:' . $name;

        return implode("\r\n", $lines);
    }

    /**
     * Write multiple components
     *
     * @param ComponentInterface[] $components The components to write
     * @return string The serialized components
     */
    public function writeMultiple(array $components): string
    {
        $lines = [];
        foreach ($components as $component) {
            $lines[] = $this->write($component);
        }
        return implode("\r\n\r\n", $lines);
    }

    /**
     * Get the property writer
     */
    public function getPropertyWriter(): PropertyWriter
    {
        return $this->propertyWriter;
    }

    /**
     * Set the property writer
     */
    public function setPropertyWriter(PropertyWriter $propertyWriter): void
    {
        $this->propertyWriter = $propertyWriter;
    }
}
