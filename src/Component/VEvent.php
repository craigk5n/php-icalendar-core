<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * VEVENT component for calendar events
 *
 * The VEVENT component defines an event on a calendar.
 */
class VEvent extends AbstractComponent
{
    public const ERR_MISSING_DTSTAMP = 'ICAL-VEVENT-001';
    public const ERR_MISSING_UID = 'ICAL-VEVENT-002';
    public const ERR_DTEND_DURATION_EXCLUSIVE = 'ICAL-VEVENT-VAL-001';
    public const ERR_DATE_CONSISTENCY = 'ICAL-VEVENT-VAL-002';
    public const ERR_INVALID_STATUS = 'ICAL-VEVENT-VAL-003';

    public const STATUS_TENTATIVE = 'TENTATIVE';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public function getName(): string
    {
        return 'VEVENT';
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

    public function setDtEnd(string $dtEnd): self
    {
        $this->removeProperty('DTEND');
        $this->addProperty(GenericProperty::create('DTEND', $dtEnd));
        return $this;
    }

    public function getDtEnd(): ?string
    {
        $prop = $this->getProperty('DTEND');
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

    public function setRrule(string $rrule): self
    {
        $this->removeProperty('RRULE');
        $this->addProperty(GenericProperty::create('RRULE', $rrule));
        return $this;
    }

    public function getRrule(): ?string
    {
        $prop = $this->getProperty('RRULE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
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

    public function setGeo(float $latitude, float $longitude): self
    {
        $geoString = sprintf('%F;%F', $latitude, $longitude);
        $this->removeProperty('GEO');
        $this->addProperty(GenericProperty::create('GEO', $geoString));
        return $this;
    }

    public function getGeo(): ?array
    {
        $prop = $this->getProperty('GEO');
        if ($prop === null) {
            return null;
        }
        $value = $prop->getValue()->getRawValue();
        if (is_string($value) && strpos($value, ';') !== false) {
            [$lat, $lon] = explode(';', $value);
            return ['latitude' => (float) $lat, 'longitude' => (float) $lon];
        }
        return null;
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
