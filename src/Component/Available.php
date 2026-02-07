<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * AVAILABLE component (RFC 7953)
 *
 * Used within VAVAILABILITY to define a specific window of availability.
 */
class Available extends AbstractComponent
{
    public const ERR_MISSING_DTSTAMP = 'ICAL-AVAIL-001';
    public const ERR_MISSING_UID = 'ICAL-AVAIL-002';

    public function getName(): string
    {
        return 'AVAILABLE';
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
        return $prop?->getValue()->getRawValue();
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
        return $prop?->getValue()->getRawValue();
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
        return $prop?->getValue()->getRawValue();
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
        return $prop?->getValue()->getRawValue();
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
        return $prop?->getValue()->getRawValue();
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
        return $prop?->getValue()->getRawValue();
    }

    public function validate(): void
    {
        if ($this->getProperty('DTSTAMP') === null) {
            throw new ValidationException(
                'AVAILABLE must contain a DTSTAMP property',
                self::ERR_MISSING_DTSTAMP
            );
        }

        if ($this->getProperty('UID') === null) {
            throw new ValidationException(
                'AVAILABLE must contain a UID property',
                self::ERR_MISSING_UID
            );
        }
    }
}
