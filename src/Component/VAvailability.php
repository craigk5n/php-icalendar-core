<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * VAVAILABILITY component (RFC 7953)
 *
 * Defines a set of available time slots for a calendar user.
 */
class VAvailability extends AbstractComponent
{
    public const ERR_MISSING_DTSTAMP = 'ICAL-VAVAIL-001';
    public const ERR_MISSING_UID = 'ICAL-VAVAIL-002';

    #[\Override]
    public function getName(): string
    {
        return 'VAVAILABILITY';
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

    public function setBusyType(string $busyType): self
    {
        $this->removeProperty('BUSYTYPE');
        $this->addProperty(GenericProperty::create('BUSYTYPE', $busyType));
        return $this;
    }

    public function getBusyType(): ?string
    {
        $prop = $this->getProperty('BUSYTYPE');
        return $prop?->getValue()->getRawValue();
    }

    /**
     * Add an AVAILABLE sub-component
     */
    public function addAvailable(Available $available): self
    {
        $this->addComponent($available);
        return $this;
    }

    /**
     * Get all AVAILABLE sub-components
     *
     * @return array<Available>
     */
    public function getAvailable(): array
    {
        /** @var array<Available> */
        return $this->getComponents('AVAILABLE');
    }

    public function validate(): void
    {
        if ($this->getProperty('DTSTAMP') === null) {
            throw new ValidationException(
                'VAVAILABILITY must contain a DTSTAMP property',
                self::ERR_MISSING_DTSTAMP
            );
        }

        if ($this->getProperty('UID') === null) {
            throw new ValidationException(
                'VAVAILABILITY must contain a UID property',
                self::ERR_MISSING_UID
            );
        }

        foreach ($this->getAvailable() as $available) {
            $available->validate();
        }
    }
}
