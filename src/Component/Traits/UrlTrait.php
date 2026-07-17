<?php

declare(strict_types=1);

namespace Icalendar\Component\Traits;

use Icalendar\Property\GenericProperty;
use Icalendar\Property\PropertyInterface;

/**
 * Shared URL property access
 *
 * RFC 5545 §3.8.4.6 permits URL on VEVENT, VTODO, VJOURNAL and VFREEBUSY. VEvent
 * and VTodo each carried their own byte-identical copy of this pair while
 * VJournal and VFreeBusy had neither, so callers of those two had to drop to
 * GenericProperty. Kept here rather than copied a third and fourth time.
 */
trait UrlTrait
{
    abstract public function removeProperty(string $name): void;

    abstract public function addProperty(PropertyInterface $property): void;

    abstract public function getProperty(string $name): ?PropertyInterface;

    /**
     * Set the URL associated with this component
     *
     * URL is single-occurrence, so any existing value is replaced.
     *
     * @param string $url The URL that provides more information about this component
     * @return self For method chaining
     */
    public function setUrl(string $url): self
    {
        $this->removeProperty('URL');
        $this->addProperty(GenericProperty::create('URL', $url));
        return $this;
    }

    /**
     * Get the URL associated with this component
     *
     * @return string|null The URL, or null if not set
     */
    public function getUrl(): ?string
    {
        $prop = $this->getProperty('URL');
        if ($prop === null) {
            return null;
        }

        return $prop->getValue()->getRawValue();
    }
}
