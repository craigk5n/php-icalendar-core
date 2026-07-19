<?php

declare(strict_types=1);

namespace Icalendar\Component\Traits;

use Icalendar\Property\GenericProperty;
use Icalendar\Property\PropertyInterface;
use Icalendar\Value\GenericValue;

/**
 * Shared REQUEST-STATUS property access.
 *
 * RFC 5545 §3.8.8.3 permits REQUEST-STATUS on VEVENT, VTODO, VJOURNAL and
 * VFREEBUSY, with the value `statcode ";" statdesc [";" extdata]`.
 *
 * The setter stores a REQUEST-STATUS-typed value rather than plain TEXT, which
 * is what routes serialisation to RequestStatusWriter. Building the property
 * with GenericProperty::create() would store TEXT, and the TEXT writer escapes
 * the structural semicolons — `2.0\;Success` — so the value could no longer be
 * split into a code and a description. setGeo() carries the same requirement
 * for the same reason.
 */
trait RequestStatusTrait
{
    abstract public function addProperty(PropertyInterface $property): void;

    /** @return PropertyInterface[] */
    abstract public function getAllProperties(string $name): array;

    /**
     * Add a REQUEST-STATUS to this component.
     *
     * REQUEST-STATUS may occur more than once, so this accumulates rather than
     * replacing.
     *
     * @param string $statusCode  Dot-separated digits, e.g. '2.0' or '3.7'
     * @param string $description Human-readable status description
     * @param string|null $extraData Optional third component
     * @return self For method chaining
     */
    public function addRequestStatus(string $statusCode, string $description, ?string $extraData = null): self
    {
        $parts = [$statusCode, $this->escapeComponent($description)];
        if ($extraData !== null) {
            $parts[] = $this->escapeComponent($extraData);
        }

        $this->addProperty(new GenericProperty(
            'REQUEST-STATUS',
            new GenericValue(implode(';', $parts), 'REQUEST-STATUS')
        ));

        return $this;
    }

    /**
     * Raw REQUEST-STATUS values on this component, in the order added.
     *
     * @return list<string>
     */
    public function getRequestStatuses(): array
    {
        $values = [];
        foreach ($this->getAllProperties('REQUEST-STATUS') as $property) {
            $values[] = $property->getValue()->getRawValue();
        }

        return $values;
    }

    /**
     * Escape a single component so a semicolon or comma inside it is not read
     * as a separator (RFC 5545 §3.3.11). The separators between components are
     * added literally by the caller above.
     */
    private function escapeComponent(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(["\r\n", "\n"], '\\n', $text);

        return str_replace("\r", '', $text);
    }
}
