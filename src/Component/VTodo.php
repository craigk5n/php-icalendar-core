<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * VTODO component for task/to-do items
 *
 * The VTODO component defines a to-do item on a calendar.
 */
class VTodo extends AbstractComponent
{
    public const ERR_MISSING_DTSTAMP = 'ICAL-VTODO-001';
    public const ERR_MISSING_UID = 'ICAL-VTODO-002';
    public const ERR_DUE_DURATION_EXCLUSIVE = 'ICAL-VTODO-VAL-001';
    public const ERR_INVALID_STATUS = 'ICAL-VTODO-VAL-002';
    public const ERR_INVALID_PERCENT_COMPLETE = 'ICAL-VTODO-VAL-003';
    public const ERR_INVALID_PRIORITY = 'ICAL-VTODO-VAL-004';

    public const STATUS_NEEDS_ACTION = 'NEEDS-ACTION';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_IN_PROCESS = 'IN-PROCESS';
    public const STATUS_CANCELLED = 'CANCELLED';

    public function getName(): string
    {
        return 'VTODO';
    }

    /**
     * Set the date-time stamp for this to-do item
     *
     * The DTSTAMP property indicates the date/time that the instance of the iCalendar
     * object was created. This is a required property for VTODO components.
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
     * Get the date-time stamp for this to-do item
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
     * Set the unique identifier for this to-do item
     *
     * The UID property provides a globally unique identifier for the to-do item.
     * This is a required property for VTODO components.
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
     * Get the unique identifier for this to-do item
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
     * Set the start date and time for this to-do item
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
     * Get the start date and time for this to-do item
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
     * Set the due date and time for this to-do item
     *
     * Note: Cannot be used together with setDuration() - either DUE or DURATION should be set, not both.
     *
     * @param string $due The due date-time in iCalendar format (e.g., "20240101T170000")
     * @return self For method chaining
     */
    public function setDue(string $due): self
    {
        $this->removeProperty('DUE');
        $this->addProperty(GenericProperty::create('DUE', $due));
        return $this;
    }

    /**
     * Get the due date and time for this to-do item
     *
     * @return string|null The due date-time or null if not set
     */
    public function getDue(): ?string
    {
        $prop = $this->getProperty('DUE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the completion date and time for this to-do item
     *
     * @param string $completed The completion date-time in iCalendar format (e.g., "20240101T150000Z")
     * @return self For method chaining
     */
    public function setCompleted(string $completed): self
    {
        $this->removeProperty('COMPLETED');
        $this->addProperty(GenericProperty::create('COMPLETED', $completed));
        return $this;
    }

    /**
     * Get the completion date and time for this to-do item
     *
     * @return string|null The completion date-time or null if not set
     */
    public function getCompleted(): ?string
    {
        $prop = $this->getProperty('COMPLETED');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the duration for this to-do item
     *
     * Note: Cannot be used together with setDue() - either DURATION or DUE should be set, not both.
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
     * Get the duration for this to-do item
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
     * Set the percentage completion for this to-do item
     *
     * @param int $percent The completion percentage (0-100, where 0=not started, 100=completed)
     * @return self For method chaining
     * @throws ValidationException If the percentage is not between 0 and 100 (code: ICAL-VTODO-VAL-003)
     */
    public function setPercentComplete(int $percent): self
    {
        if ($percent < 0 || $percent > 100) {
            throw new ValidationException(
                "Invalid PERCENT-COMPLETE: {$percent}. Must be 0-100",
                self::ERR_INVALID_PERCENT_COMPLETE
            );
        }
        $this->removeProperty('PERCENT-COMPLETE');
        $this->addProperty(GenericProperty::create('PERCENT-COMPLETE', (string) $percent));
        return $this;
    }

    /**
     * Get the percentage completion for this to-do item
     *
     * @return int|null The completion percentage or null if not set
     */
    public function getPercentComplete(): ?int
    {
        $prop = $this->getProperty('PERCENT-COMPLETE');
        if ($prop === null) {
            return null;
        }
        return (int) $prop->getValue()->getRawValue();
    }

    /**
     * Set the priority for this to-do item
     *
     * @param int $priority The priority level (0-9, where 0=undefined, 1=highest priority, 9=lowest)
     * @return self For method chaining
     * @throws ValidationException If the priority is not between 0 and 9 (code: ICAL-VTODO-VAL-004)
     */
    public function setPriority(int $priority): self
    {
        if ($priority < 0 || $priority > 9) {
            throw new ValidationException(
                "Invalid PRIORITY: {$priority}. Must be 0-9 (0=undefined, 1=highest)",
                self::ERR_INVALID_PRIORITY
            );
        }
        $this->removeProperty('PRIORITY');
        $this->addProperty(GenericProperty::create('PRIORITY', (string) $priority));
        return $this;
    }

    /**
     * Get the priority for this to-do item
     *
     * @return int|null The priority level or null if not set
     */
    public function getPriority(): ?int
    {
        $prop = $this->getProperty('PRIORITY');
        if ($prop === null) {
            return null;
        }
        return (int) $prop->getValue()->getRawValue();
    }

    /**
     * Set the summary/title for this to-do item
     *
     * @param string $summary The to-do item title or short description
     * @return self For method chaining
     */
    public function setSummary(string $summary): self
    {
        $this->removeProperty('SUMMARY');
        $this->addProperty(GenericProperty::create('SUMMARY', $summary));
        return $this;
    }

    /**
     * Get the summary/title for this to-do item
     *
     * @return string|null The to-do item summary or null if not set
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
     * Set the detailed description for this to-do item
     *
     * @param string $description The full description of the to-do item
     * @return self For method chaining
     */
    public function setDescription(string $description): self
    {
        $this->removeProperty('DESCRIPTION');
        $this->addProperty(GenericProperty::create('DESCRIPTION', $description));
        return $this;
    }

    /**
     * Get the detailed description for this to-do item
     *
     * @return string|null The to-do item description or null if not set
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
     * Set the location for this to-do item
     *
     * @param string $location The venue or location where the to-do should be done
     * @return self For method chaining
     */
    public function setLocation(string $location): self
    {
        $this->removeProperty('LOCATION');
        $this->addProperty(GenericProperty::create('LOCATION', $location));
        return $this;
    }

    /**
     * Get the location for this to-do item
     *
     * @return string|null The to-do item location or null if not set
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
     * Set the URL associated with this to-do item
     *
     * @param string $url The URL that provides more information about the to-do item
     * @return self For method chaining
     */
    public function setUrl(string $url): self
    {
        $this->removeProperty('URL');
        $this->addProperty(GenericProperty::create('URL', $url));
        return $this;
    }

    /**
     * Get the URL associated with this to-do item
     *
     * @return string|null The to-do item URL or null if not set
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
     * Set the status for this to-do item
     *
     * @param string $status The to-do status (must be NEEDS-ACTION, COMPLETED, IN-PROCESS, or CANCELLED)
     * @return self For method chaining
     * @throws ValidationException If the status is not one of the allowed values (code: ICAL-VTODO-VAL-002)
     */
    public function setStatus(string $status): self
    {
        $validStatuses = [
            self::STATUS_NEEDS_ACTION,
            self::STATUS_COMPLETED,
            self::STATUS_IN_PROCESS,
            self::STATUS_CANCELLED,
        ];
        if (!in_array($status, $validStatuses, true)) {
            throw new ValidationException(
                "Invalid VTODO status: {$status}. Must be NEEDS-ACTION, COMPLETED, IN-PROCESS, or CANCELLED",
                self::ERR_INVALID_STATUS
            );
        }
        $this->removeProperty('STATUS');
        $this->addProperty(GenericProperty::create('STATUS', $status));
        return $this;
    }

    /**
     * Get the status for this to-do item
     *
     * @return string|null The to-do status or null if not set
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
     * Set the categories for this to-do item
     *
     * @param string ...$categories One or more category names to classify the to-do item
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
     * Get the categories for this to-do item
     *
     * @return array Array of category names, empty if not set
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
     * Add an alarm to this to-do item
     *
     * @param object $alarm The VALARM component to add
     * @return self For method chaining
     */
    public function addAlarm(object $alarm): self
    {
        $this->addComponent($alarm);
        return $this;
    }

    /**
     * Get all alarms associated with this to-do item
     *
     * @return array Array of VALARM components
     */
    public function getAlarms(): array
    {
        return $this->getComponents('VALARM');
    }

    /**
     * Validate this VTODO component against RFC 5545 requirements
     *
     * Ensures that required properties (DTSTAMP and UID) are present and that
     * DUE and DURATION are not both set (they are mutually exclusive).
     *
     * @throws ValidationException If DTSTAMP property is missing (code: ICAL-VTODO-001)
     * @throws ValidationException If UID property is missing (code: ICAL-VTODO-002)
     * @throws ValidationException If both DUE and DURATION properties are present (code: ICAL-VTODO-VAL-001)
     * @return void
     */
    public function validate(): void
    {
        if ($this->getProperty('DTSTAMP') === null) {
            throw new ValidationException(
                'VTODO must contain a DTSTAMP property',
                self::ERR_MISSING_DTSTAMP
            );
        }

        if ($this->getProperty('UID') === null) {
            throw new ValidationException(
                'VTODO must contain a UID property',
                self::ERR_MISSING_UID
            );
        }

        $hasDue = $this->getProperty('DUE') !== null;
        $hasDuration = $this->getProperty('DURATION') !== null;

        if ($hasDue && $hasDuration) {
            throw new ValidationException(
                'VTODO cannot have both DUE and DURATION properties',
                self::ERR_DUE_DURATION_EXCLUSIVE
            );
        }
    }
}
