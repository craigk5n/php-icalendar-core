<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * Root VCALENDAR component
 *
 * The VCALENDAR component is the top-level container for all iCalendar data.
 * It must contain at least one of each required property.
 */
class VCalendar extends AbstractComponent
{
    public const ERR_MISSING_PRODID = 'ICAL-COMP-001';
    public const ERR_MISSING_VERSION = 'ICAL-COMP-002';

    public function getName(): string
    {
        return 'VCALENDAR';
    }

    /**
     * Set the product identifier for this calendar
     *
     * @param string $prodId The product identifier that uniquely identifies the software
     *                      that created the iCalendar object (e.g., "-//My Company//My App//EN")
     * @return self For method chaining
     */
    public function setProductId(string $prodId): self
    {
        $this->removeProperty('PRODID');
        $this->addProperty(GenericProperty::create('PRODID', $prodId));
        return $this;
    }

    /**
     * Get the product identifier for this calendar
     *
     * @return string|null The product identifier or null if not set
     */
    public function getProductId(): ?string
    {
        $prop = $this->getProperty('PRODID');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the iCalendar specification version
     *
     * @param string $version The iCalendar version (e.g., "2.0" for RFC 5545)
     * @return self For method chaining
     */
    public function setVersion(string $version): self
    {
        $this->removeProperty('VERSION');
        $this->addProperty(GenericProperty::create('VERSION', $version));
        return $this;
    }

    /**
     * Get the iCalendar specification version
     *
     * @return string|null The version string or null if not set
     */
    public function getVersion(): ?string
    {
        $prop = $this->getProperty('VERSION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the calendar scale used by this calendar
     *
     * @param string $calscale The calendar scale (e.g., "GREGORIAN")
     * @return self For method chaining
     */
    public function setCalscale(string $calscale): self
    {
        $this->removeProperty('CALSCALE');
        $this->addProperty(GenericProperty::create('CALSCALE', $calscale));
        return $this;
    }

    /**
     * Get the calendar scale used by this calendar
     *
     * @return string|null The calendar scale or null if not set
     */
    public function getCalscale(): ?string
    {
        $prop = $this->getProperty('CALSCALE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the method type for this calendar (used for iTIP messages)
     *
     * @param string $method The method type (e.g., "PUBLISH", "REQUEST", "REPLY", "ADD", "CANCEL", "REFRESH", "COUNTER", "DECLINECOUNTER")
     * @return self For method chaining
     */
    public function setMethod(string $method): self
    {
        $this->removeProperty('METHOD');
        $this->addProperty(GenericProperty::create('METHOD', $method));
        return $this;
    }

    /**
     * Get the method type for this calendar
     *
     * @return string|null The method type or null if not set
     */
    public function getMethod(): ?string
    {
        $prop = $this->getProperty('METHOD');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Validate this VCALENDAR component against RFC 5545 requirements
     *
     * Ensures that all required properties (PRODID and VERSION) are present.
     * Throws ValidationException if the calendar is not valid.
     *
     * @throws ValidationException If PRODID property is missing (code: ICAL-COMP-001)
     * @throws ValidationException If VERSION property is missing (code: ICAL-COMP-002)
     * @return void
     */
    public function validate(): void
    {
        if ($this->getProperty('PRODID') === null) {
            throw new ValidationException(
                'VCALENDAR must contain a PRODID property',
                self::ERR_MISSING_PRODID
            );
        }

        if ($this->getProperty('VERSION') === null) {
            throw new ValidationException(
                'VCALENDAR must contain a VERSION property',
                self::ERR_MISSING_VERSION
            );
        }
    }
}
