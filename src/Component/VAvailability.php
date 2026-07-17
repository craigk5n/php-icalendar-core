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

    #[\Override]
    protected function validateSelf(): void
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

        // Each AVAILABLE child is validated by AbstractComponent::validate()'s descent.
    }
}
