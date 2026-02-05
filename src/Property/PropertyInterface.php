<?php

declare(strict_types=1);

namespace Icalendar\Property;

use Icalendar\Value\ValueInterface;

/**
 * Interface for all iCalendar properties
 */
interface PropertyInterface
{
    /**
     * Get the property name
     */
    public function getName(): string;

    /**
     * Get the property value
     */
    public function getValue(): ValueInterface;

    /**
     * Set the property value
     */
    public function setValue(ValueInterface $value): void;

    /**
     * Get a parameter value
     */
    public function getParameter(string $name): ?string;

    /**
     * Get all parameters
     *
     * @return array<string, string>
     */
    public function getParameters(): array;

    /**
     * Set a parameter
     */
    public function setParameter(string $name, string $value): void;

    /**
     * Remove a parameter
     */
    public function removeParameter(string $name): void;
}
