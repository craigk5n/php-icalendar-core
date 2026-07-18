<?php

declare(strict_types=1);

namespace Icalendar\Component\Traits;

use Icalendar\Property\GenericProperty;
use Icalendar\Property\PropertyInterface;
use Icalendar\Value\TextListValue;

/**
 * Shared CATEGORIES property access.
 *
 * RFC 5545 §3.8.1.2 permits CATEGORIES on VEVENT, VTODO and VJOURNAL, and its
 * value is a comma-separated list of TEXT values (`text *("," text)`). VEvent,
 * VTodo and VJournal each carried a byte-identical copy that joined the values
 * into a single TEXT value before writing, so the writer escaped the separator
 * commas and a strict parser saw one category. Storing the values as a
 * TextListValue keeps the list structure intact through serialisation; kept
 * here rather than fixed in three places.
 */
trait CategoriesTrait
{
    abstract public function removeProperty(string $name): void;

    abstract public function addProperty(PropertyInterface $property): void;

    abstract public function getProperty(string $name): ?PropertyInterface;

    /**
     * Set the categories for this component.
     *
     * Empty names are dropped, so `setCategories('')` clears the list rather
     * than emitting a spurious empty value.
     *
     * @param string ...$categories One or more category names
     * @return self For method chaining
     */
    public function setCategories(string ...$categories): self
    {
        $this->removeProperty('CATEGORIES');
        $items = array_values(array_filter($categories, static fn (string $c): bool => $c !== ''));
        $this->addProperty(new GenericProperty('CATEGORIES', new TextListValue($items)));
        return $this;
    }

    /**
     * Get the categories for this component.
     *
     * @return list<string> Category names, empty if not set
     */
    public function getCategories(): array
    {
        $prop = $this->getProperty('CATEGORIES');
        if ($prop === null) {
            return [];
        }

        $value = $prop->getValue();
        if ($value instanceof TextListValue) {
            return $value->getItems();
        }

        // Fallback for a CATEGORIES value added as plain TEXT (e.g. via
        // GenericProperty directly): split on unescaped commas and unescape
        // each, symmetric with how the list is written.
        return TextListValue::fromRawValue($value->getRawValue())->getItems();
    }
}
