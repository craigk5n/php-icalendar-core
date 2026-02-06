<?php

declare(strict_types=1);

namespace Icalendar\Property;

use Icalendar\Value\TextValue;
use Icalendar\Value\ValueInterface;

    /**
     * Generic property implementation for simple string values
     *
     * A generic property that can represent any iCalendar property with a name,
     * value, and optional parameters. This is the most commonly used property type.
     */
    class GenericProperty implements PropertyInterface
    {
    /**
     * Create a new GenericProperty instance
     *
     * @param string $name The property name (e.g., "SUMMARY", "DTSTART")
     * @param ValueInterface $value The property value object
     * @param array $parameters Optional array of property parameters (key-value pairs)
     */
    public function __construct(
        private string $name,
        private ValueInterface $value,
        private array $parameters = []
    ) {}

    /**
     * Get the property name
     *
     * @return string The property name (e.g., "SUMMARY", "DTSTART")
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the property value
     *
     * @return ValueInterface The property value object
     */
    public function getValue(): ValueInterface
    {
        return $this->value;
    }

    /**
     * Set the property value
     *
     * @param ValueInterface $value The new property value object
     * @return void
     */
    public function setValue(ValueInterface $value): void
    {
        $this->value = $value;
    }

    /**
     * Get a specific parameter value
     *
     * @param string $name The parameter name (e.g., "LANGUAGE", "CN")
     * @return string|null The parameter value or null if not set
     */
    public function getParameter(string $name): ?string
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Get all parameters for this property
     *
     * @return array Associative array of parameter name-value pairs
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Set a parameter value
     *
     * @param string $name The parameter name (e.g., "LANGUAGE", "CN")
     * @param string $value The parameter value
     * @return void
     */
    public function setParameter(string $name, string $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Remove a parameter from this property
     *
     * @param string $name The parameter name to remove
     * @return void
     */
    public function removeParameter(string $name): void
    {
        unset($this->parameters[$name]);
    }

    /**
     * Create a new GenericProperty with a simple text value
     *
     * Factory method that creates a GenericProperty with a TextValue instance.
     * This is a convenient shortcut for the most common use case.
     *
     * @param string $name The property name (e.g., "SUMMARY", "DESCRIPTION")
     * @param string $value The text value for the property
     * @return self A new GenericProperty instance
     */
    public static function create(string $name, string $value): self
    {
        return new self($name, new TextValue($value));
    }
}
