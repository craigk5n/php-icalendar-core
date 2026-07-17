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

    #[\Override]
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

    /**
     * Set the dtStamp property
     *
     * @param string|\DateTimeInterface $dtStamp A value string, or a date object to format
     * @param array<string, string> $params Parameters to attach, e.g. ['TZID' => 'America/New_York'] or ['VALUE' => 'DATE']
     * @return self For method chaining
     */
    public function setDtStamp(string|\DateTimeInterface $dtStamp, array $params = []): self
    {
        $this->setDateProperty('DTSTAMP', $dtStamp, $params);
        return $this;
    }

    public function getDtStamp(): ?string
    {
        $prop = $this->getProperty('DTSTAMP');
        return $prop?->getValue()->getRawValue();
    }

    /**
     * Set the dtStart property
     *
     * @param string|\DateTimeInterface $dtStart A value string, or a date object to format
     * @param array<string, string> $params Parameters to attach, e.g. ['TZID' => 'America/New_York'] or ['VALUE' => 'DATE']
     * @return self For method chaining
     */
    public function setDtStart(string|\DateTimeInterface $dtStart, array $params = []): self
    {
        $this->setDateProperty('DTSTART', $dtStart, $params);
        return $this;
    }

    public function getDtStart(): ?string
    {
        $prop = $this->getProperty('DTSTART');
        return $prop?->getValue()->getRawValue();
    }

    /**
     * Set the dtEnd property
     *
     * @param string|\DateTimeInterface $dtEnd A value string, or a date object to format
     * @param array<string, string> $params Parameters to attach, e.g. ['TZID' => 'America/New_York'] or ['VALUE' => 'DATE']
     * @return self For method chaining
     */
    public function setDtEnd(string|\DateTimeInterface $dtEnd, array $params = []): self
    {
        $this->setDateProperty('DTEND', $dtEnd, $params);
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

    #[\Override]
    protected function validateSelf(): void
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
