<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Component\Traits\RecurrenceTrait;
use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * VEVENT component for calendar events
 *
 * The VEVENT component defines an event on a calendar.
 */
class VEvent extends AbstractComponent
{
    use RecurrenceTrait;

    public const ERR_MISSING_DTSTAMP = 'ICAL-VEVENT-001';
    public const ERR_MISSING_UID = 'ICAL-VEVENT-002';
    public const ERR_DTEND_DURATION_EXCLUSIVE = 'ICAL-VEVENT-VAL-001';
    public const ERR_DATE_CONSISTENCY = 'ICAL-VEVENT-VAL-002';
    public const ERR_INVALID_STATUS = 'ICAL-VEVENT-VAL-003';

    public const STATUS_TENTATIVE = 'TENTATIVE';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[\Override]
    public function getName(): string
    {
        return 'VEVENT';
    }

    /**
     * Set the date-time stamp for this event
     *
     * The DTSTAMP property indicates the date/time that the instance of the iCalendar
     * object was created. This is a required property for VEVENT components.
     *
     * @param string $dtStamp The date-time stamp in iCalendar format (e.g., "20231231T235959Z")
     * @return self For method chaining
     */
    public function setDtStamp(string $dtStamp): self
    {
        $this->removeProperty('DTSTAMP');
        $this->addProperty(GenericProperty::create('DTSTAMP', $dtStamp));
        return $this;
    }

    /**
     * Get the date-time stamp for this event
     *
     * @return string|null The date-time stamp or null if not set
     */
    public function getDtStamp(): ?string
    {
        $prop = $this->getProperty('DTSTAMP');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the unique identifier for this event
     *
     * The UID property provides a globally unique identifier for the event.
     * This is a required property for VEVENT components.
     *
     * @param string $uid The unique identifier (e.g., "123456789@example.com")
     * @return self For method chaining
     */
    public function setUid(string $uid): self
    {
        $this->removeProperty('UID');
        $this->addProperty(GenericProperty::create('UID', $uid));
        return $this;
    }

    /**
     * Get the unique identifier for this event
     *
     * @return string|null The unique identifier or null if not set
     */
    public function getUid(): ?string
    {
        $prop = $this->getProperty('UID');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the start date and time for this event
     *
     * @param string $dtStart The start date-time in iCalendar format (e.g., "20231231T100000")
     * @return self For method chaining
     */
    public function setDtStart(string $dtStart): self
    {
        $this->removeProperty('DTSTART');
        $this->addProperty(GenericProperty::create('DTSTART', $dtStart));
        return $this;
    }

    /**
     * Get the start date and time for this event
     *
     * @return string|null The start date-time or null if not set
     */
    public function getDtStart(): ?string
    {
        $prop = $this->getProperty('DTSTART');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the end date and time for this event
     *
     * Note: Cannot be used together with setDuration() - either DTEND or DURATION should be set, not both.
     *
     * @param string $dtEnd The end date-time in iCalendar format (e.g., "20231231T110000")
     * @return self For method chaining
     */
    public function setDtEnd(string $dtEnd): self
    {
        $this->removeProperty('DTEND');
        $this->addProperty(GenericProperty::create('DTEND', $dtEnd));
        return $this;
    }

    /**
     * Get the end date and time for this event
     *
     * @return string|null The end date-time or null if not set
     */
    public function getDtEnd(): ?string
    {
        $prop = $this->getProperty('DTEND');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the duration for this event
     *
     * Note: Cannot be used together with setDtEnd() - either DURATION or DTEND should be set, not both.
     *
     * @param string $duration The duration in ISO 8601 format (e.g., "PT1H" for 1 hour)
     * @return self For method chaining
     */
    public function setDuration(string $duration): self
    {
        $this->removeProperty('DURATION');
        $this->addProperty(GenericProperty::create('DURATION', $duration));
        return $this;
    }

    /**
     * Get the duration for this event
     *
     * @return string|null The duration string or null if not set
     */
    public function getDuration(): ?string
    {
        $prop = $this->getProperty('DURATION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the recurrence rule for this event
     *
     * @param string $rrule The recurrence rule in RFC 5545 format (e.g., "FREQ=WEEKLY;BYDAY=MO")
     * @return self For method chaining
     */
    public function setRrule(string $rrule): self
    {
        $this->removeProperty('RRULE');
        $this->addProperty(GenericProperty::create('RRULE', $rrule));
        return $this;
    }

    /**
     * Get the recurrence rule for this event
     *
     * @return string|null The recurrence rule or null if not set
     */
    public function getRrule(): ?string
    {
        $prop = $this->getProperty('RRULE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the summary/title for this event
     *
     * @param string $summary The event title or short description
     * @return self For method chaining
     */
    public function setSummary(string $summary): self
    {
        $this->removeProperty('SUMMARY');
        $this->addProperty(GenericProperty::create('SUMMARY', $summary));
        return $this;
    }

    /**
     * Get the summary/title for this event
     *
     * @return string|null The event summary or null if not set
     */
    public function getSummary(): ?string
    {
        $prop = $this->getProperty('SUMMARY');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the detailed description for this event
     *
     * @param string $description The full description of the event
     * @return self For method chaining
     */
    public function setDescription(string $description): self
    {
        $this->removeProperty('DESCRIPTION');
        $this->addProperty(GenericProperty::create('DESCRIPTION', $description));
        return $this;
    }

    /**
     * Get the detailed description for this event
     *
     * @return string|null The event description or null if not set
     */
    public function getDescription(): ?string
    {
        $prop = $this->getProperty('DESCRIPTION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the location for this event
     *
     * @param string $location The venue or location where the event takes place
     * @return self For method chaining
     */
    public function setLocation(string $location): self
    {
        $this->removeProperty('LOCATION');
        $this->addProperty(GenericProperty::create('LOCATION', $location));
        return $this;
    }

    /**
     * Get the location for this event
     *
     * @return string|null The event location or null if not set
     */
    public function getLocation(): ?string
    {
        $prop = $this->getProperty('LOCATION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the status for this event
     *
     * @param string $status The event status (must be TENTATIVE, CONFIRMED, or CANCELLED)
     * @return self For method chaining
     * @throws ValidationException If the status is not one of the allowed values (code: ICAL-VEVENT-VAL-003)
     */
    public function setStatus(string $status): self
    {
        $validStatuses = [self::STATUS_TENTATIVE, self::STATUS_CONFIRMED, self::STATUS_CANCELLED];
        if (!in_array($status, $validStatuses, true)) {
            throw new ValidationException(
                "Invalid VEVENT status: {$status}. Must be TENTATIVE, CONFIRMED, or CANCELLED",
                self::ERR_INVALID_STATUS
            );
        }
        $this->removeProperty('STATUS');
        $this->addProperty(GenericProperty::create('STATUS', $status));
        return $this;
    }

    /**
     * Get the status for this event
     *
     * @return string|null The event status or null if not set
     */
    public function getStatus(): ?string
    {
        $prop = $this->getProperty('STATUS');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the categories for this event
     *
     * @param string ...$categories One or more category names to classify the event
     * @return self For method chaining
     */
    public function setCategories(string ...$categories): self
    {
        $this->removeProperty('CATEGORIES');
        $categoriesValue = implode(',', $categories);
        $this->addProperty(GenericProperty::create('CATEGORIES', $categoriesValue));
        return $this;
    }

    /**
     * Get the categories for this event
     *
     * @return array<string> Array of category names, empty if not set
     */
    public function getCategories(): array
    {
        $prop = $this->getProperty('CATEGORIES');
        if ($prop === null) {
            return [];
        }
        $value = $prop->getValue()->getRawValue();
        if ($value === '') {
            return [];
        }
        return explode(',', $value);
    }

    /**
     * Set the URL associated with this event
     *
     * @param string $url The URL that provides more information about the event
     * @return self For method chaining
     */
    public function setUrl(string $url): self
    {
        $this->removeProperty('URL');
        $this->addProperty(GenericProperty::create('URL', $url));
        return $this;
    }

    /**
     * Get the URL associated with this event
     *
     * @return string|null The event URL or null if not set
     */
    public function getUrl(): ?string
    {
        $prop = $this->getProperty('URL');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the geographic coordinates for this event
     *
     * @param float $latitude The latitude coordinate
     * @param float $longitude The longitude coordinate
     * @return self For method chaining
     */
    public function setGeo(float $latitude, float $longitude): self
    {
        $geoString = sprintf('%F;%F', $latitude, $longitude);
        $this->removeProperty('GEO');
        $this->addProperty(GenericProperty::create('GEO', $geoString));
        return $this;
    }

    /**
     * Get the geographic coordinates for this event
     *
     * @return array{latitude: float, longitude: float}|null Array with 'latitude' and 'longitude' keys, or null if not set
     */
    public function getGeo(): ?array
    {
        $prop = $this->getProperty('GEO');
        if ($prop === null) {
            return null;
        }
        $value = $prop->getValue()->getRawValue();
        if (strpos($value, ';') !== false) {
            [$lat, $lon] = explode(';', $value);
            return ['latitude' => (float) $lat, 'longitude' => (float) $lon];
        }
        return null;
    }

    /**
     * Add an alarm to this event
     *
     * @param VAlarm $alarm The VALARM component to add
     * @return self For method chaining
     */
    public function addAlarm(VAlarm $alarm): self
    {
        $this->addComponent($alarm);
        return $this;
    }

    /**
     * Get all alarms associated with this event
     *
     * @return array<VAlarm> Array of VALARM components
     */
    public function getAlarms(): array
    {
        /** @var array<VAlarm> */
        return $this->getComponents('VALARM');
    }

    /**
     * Set the IMAGE property for this event
     *
     * @param string $image The image URI or binary data
     * @param string $valueType The value type (default 'URI', can be 'BINARY')
     * @return self For method chaining
     */
    public function setImage(string $image, string $valueType = 'URI'): self
    {
        $this->removeProperty('IMAGE');
        $params = [];
        if (strtoupper($valueType) !== 'URI') {
            $params['VALUE'] = strtoupper($valueType);
        }
        $this->addProperty(new GenericProperty('IMAGE', new \Icalendar\Value\GenericValue($image, $valueType), $params));
        return $this;
    }

    /**
     * Get the IMAGE property for this event
     *
     * @return string|null The image URI or data or null if not set
     */
    public function getImage(): ?string
    {
        $prop = $this->getProperty('IMAGE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the COLOR property for this event
     *
     * @param string $color The CSS3 color name or hex code (e.g., "blue", "#0000FF")
     * @return self For method chaining
     */
    public function setColor(string $color): self
    {
        $this->removeProperty('COLOR');
        $this->addProperty(GenericProperty::create('COLOR', $color));
        return $this;
    }

    /**
     * Get the COLOR property for this event
     *
     * @return string|null The color name/code or null if not set
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
     * Set the CONFERENCE property for this event
     *
     * @param string $conference The conference URI (e.g., Zoom/Meet link)
     * @return self For method chaining
     */
    public function setConference(string $conference): self
    {
        $this->removeProperty('CONFERENCE');
        $this->addProperty(GenericProperty::create('CONFERENCE', $conference));
        return $this;
    }

    /**
     * Get the CONFERENCE property for this event
     *
     * @return string|null The conference URI or null if not set
     */
    public function getConference(): ?string
    {
        $prop = $this->getProperty('CONFERENCE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set structured location (Apple extension)
     */
    public function setAppleStructuredLocation(string $value, array $parameters = []): self
    {
        $this->removeProperty('X-APPLE-STRUCTURED-LOCATION');
        $this->addProperty(new GenericProperty('X-APPLE-STRUCTURED-LOCATION', new \Icalendar\Value\TextValue($value), $parameters));
        return $this;
    }

    /**
     * Get structured location (Apple extension)
     */
    public function getAppleStructuredLocation(): ?string
    {
        $prop = $this->getProperty('X-APPLE-STRUCTURED-LOCATION');
        return $prop?->getValue()->getRawValue();
    }

    /**
     * Validate this VEVENT component against RFC 5545 requirements
     *
     * Ensures that required properties (DTSTAMP and UID) are present and that
     * DTEND and DURATION are not both set (they are mutually exclusive).
     *
     * @throws ValidationException If DTSTAMP property is missing (code: ICAL-VEVENT-001)
     * @throws ValidationException If UID property is missing (code: ICAL-VEVENT-002)
     * @throws ValidationException If both DTEND and DURATION properties are present (code: ICAL-VEVENT-VAL-001)
     * @return void
     */
    public function validate(): void
    {
        if ($this->getProperty('DTSTAMP') === null) {
            throw new ValidationException(
                'VEVENT must contain a DTSTAMP property',
                self::ERR_MISSING_DTSTAMP
            );
        }

        if ($this->getProperty('UID') === null) {
            throw new ValidationException(
                'VEVENT must contain a UID property',
                self::ERR_MISSING_UID
            );
        }

        $hasDtEnd = $this->getProperty('DTEND') !== null;
        $hasDuration = $this->getProperty('DURATION') !== null;

        if ($hasDtEnd && $hasDuration) {
            throw new ValidationException(
                'VEVENT cannot have both DTEND and DURATION properties',
                self::ERR_DTEND_DURATION_EXCLUSIVE
            );
        }
    }
}
