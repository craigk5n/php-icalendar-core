<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * PARTICIPANT component (RFC 9073)
 *
 * Provides a richer alternative to ATTENDEE for describing participants.
 */
class Participant extends AbstractComponent
{
    public function getName(): string
    {
        return 'PARTICIPANT';
    }

    public function setParticipantType(string $type): self
    {
        $this->removeProperty('PARTICIPANT-TYPE');
        $this->addProperty(GenericProperty::create('PARTICIPANT-TYPE', $type));
        return $this;
    }

    public function getParticipantType(): ?string
    {
        $prop = $this->getProperty('PARTICIPANT-TYPE');
        return $prop?->getValue()->getRawValue();
    }

    public function setCalendarAddress(string $address): self
    {
        $this->removeProperty('CAL-ADDRESS');
        $this->addProperty(GenericProperty::create('CAL-ADDRESS', $address));
        return $this;
    }

    public function getCalendarAddress(): ?string
    {
        $prop = $this->getProperty('CAL-ADDRESS');
        return $prop?->getValue()->getRawValue();
    }

    public function validate(): void
    {
        if ($this->getProperty('PARTICIPANT-TYPE') === null) {
            // RFC 9073 says PARTICIPANT-TYPE is required
            throw new ValidationException('PARTICIPANT component missing required PARTICIPANT-TYPE property');
        }
    }
}
