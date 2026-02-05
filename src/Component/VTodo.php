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

    public function setDtStamp(string $dtStamp): self
    {
        $this->removeProperty('DTSTAMP');
        $this->addProperty(GenericProperty::create('DTSTAMP', $dtStamp));
        return $this;
    }

    public function getDtStamp(): ?string
    {
        $prop = $this->getProperty('DTSTAMP');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setUid(string $uid): self
    {
        $this->removeProperty('UID');
        $this->addProperty(GenericProperty::create('UID', $uid));
        return $this;
    }

    public function getUid(): ?string
    {
        $prop = $this->getProperty('UID');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setDtStart(string $dtStart): self
    {
        $this->removeProperty('DTSTART');
        $this->addProperty(GenericProperty::create('DTSTART', $dtStart));
        return $this;
    }

    public function getDtStart(): ?string
    {
        $prop = $this->getProperty('DTSTART');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setDue(string $due): self
    {
        $this->removeProperty('DUE');
        $this->addProperty(GenericProperty::create('DUE', $due));
        return $this;
    }

    public function getDue(): ?string
    {
        $prop = $this->getProperty('DUE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setCompleted(string $completed): self
    {
        $this->removeProperty('COMPLETED');
        $this->addProperty(GenericProperty::create('COMPLETED', $completed));
        return $this;
    }

    public function getCompleted(): ?string
    {
        $prop = $this->getProperty('COMPLETED');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setDuration(string $duration): self
    {
        $this->removeProperty('DURATION');
        $this->addProperty(GenericProperty::create('DURATION', $duration));
        return $this;
    }

    public function getDuration(): ?string
    {
        $prop = $this->getProperty('DURATION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

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

    public function getPercentComplete(): ?int
    {
        $prop = $this->getProperty('PERCENT-COMPLETE');
        if ($prop === null) {
            return null;
        }
        return (int) $prop->getValue()->getRawValue();
    }

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

    public function getPriority(): ?int
    {
        $prop = $this->getProperty('PRIORITY');
        if ($prop === null) {
            return null;
        }
        return (int) $prop->getValue()->getRawValue();
    }

    public function setSummary(string $summary): self
    {
        $this->removeProperty('SUMMARY');
        $this->addProperty(GenericProperty::create('SUMMARY', $summary));
        return $this;
    }

    public function getSummary(): ?string
    {
        $prop = $this->getProperty('SUMMARY');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setDescription(string $description): self
    {
        $this->removeProperty('DESCRIPTION');
        $this->addProperty(GenericProperty::create('DESCRIPTION', $description));
        return $this;
    }

    public function getDescription(): ?string
    {
        $prop = $this->getProperty('DESCRIPTION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setLocation(string $location): self
    {
        $this->removeProperty('LOCATION');
        $this->addProperty(GenericProperty::create('LOCATION', $location));
        return $this;
    }

    public function getLocation(): ?string
    {
        $prop = $this->getProperty('LOCATION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setUrl(string $url): self
    {
        $this->removeProperty('URL');
        $this->addProperty(GenericProperty::create('URL', $url));
        return $this;
    }

    public function getUrl(): ?string
    {
        $prop = $this->getProperty('URL');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

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

    public function getStatus(): ?string
    {
        $prop = $this->getProperty('STATUS');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setCategories(string ...$categories): self
    {
        $this->removeProperty('CATEGORIES');
        $categoriesValue = implode(',', $categories);
        $this->addProperty(GenericProperty::create('CATEGORIES', $categoriesValue));
        return $this;
    }

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

    public function addAlarm(object $alarm): self
    {
        $this->addComponent($alarm);
        return $this;
    }

    public function getAlarms(): array
    {
        return $this->getComponents('VALARM');
    }

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
