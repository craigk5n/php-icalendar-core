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

    #[\Override]
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
     * Set the refresh interval for this calendar (RFC 7986)
     *
     * @param string $interval The duration string (e.g., "PT1H")
     * @return self For method chaining
     */
    public function setRefreshInterval(string $interval): self
    {
        $this->removeProperty('REFRESH-INTERVAL');
        $this->addProperty(GenericProperty::create('REFRESH-INTERVAL', $interval));
        return $this;
    }

    /**
     * Get the refresh interval for this calendar
     *
     * @return string|null The interval duration or null if not set
     */
    public function getRefreshInterval(): ?string
    {
        $prop = $this->getProperty('REFRESH-INTERVAL');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the COLOR property for this calendar (RFC 7986)
     *
     * @param string $color The color name or code
     * @return self For method chaining
     */
    public function setColor(string $color): self
    {
        $this->removeProperty('COLOR');
        $this->addProperty(GenericProperty::create('COLOR', $color));
        return $this;
    }

    /**
     * Get the COLOR property for this calendar
     *
     * @return string|null The color or null if not set
     */
    public function getColor(): ?string
    {
        $prop = $this->getProperty('COLOR');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the display name of the calendar (Common extension)
     */
    public function setCalendarName(string $name): self
    {
        $this->removeProperty('X-WR-CALNAME');
        $this->addProperty(GenericProperty::create('X-WR-CALNAME', $name));
        return $this;
    }

    /**
     * Get the display name of the calendar
     */
    public function getCalendarName(): ?string
    {
        $prop = $this->getProperty('X-WR-CALNAME');
        return $prop?->getValue()->getRawValue();
    }

    /**
     * Set the default timezone for the calendar (Common extension)
     */
    public function setCalendarTimezone(string $tzid): self
    {
        $this->removeProperty('X-WR-TIMEZONE');
        $this->addProperty(GenericProperty::create('X-WR-TIMEZONE', $tzid));
        return $this;
    }

    /**
     * Get the default timezone for the calendar
     */
    public function getCalendarTimezone(): ?string
    {
        $prop = $this->getProperty('X-WR-TIMEZONE');
        return $prop?->getValue()->getRawValue();
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

    /**
     * Convert the entire calendar to jCal JSON string
     */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->toArray(), $options);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode iCalendar to JSON: ' . json_last_error_msg());
        }
        return $json;
    }
}
