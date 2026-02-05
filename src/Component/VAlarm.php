<?php

declare(strict_types=1);

namespace Icalendar\Component;

use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;

/**
 * VALARM component for event reminders
 *
 * The VALARM component defines an alarm/reminder for an event.
 */
class VAlarm extends AbstractComponent
{
    public const ERR_MISSING_ACTION = 'ICAL-ALARM-001';
    public const ERR_MISSING_TRIGGER = 'ICAL-ALARM-002';

    public const ACTION_AUDIO = 'AUDIO';
    public const ACTION_DISPLAY = 'DISPLAY';
    public const ACTION_EMAIL = 'EMAIL';

    public function getName(): string
    {
        return 'VALARM';
    }

    public function setAction(string $action): self
    {
        $this->removeProperty('ACTION');
        $this->addProperty(GenericProperty::create('ACTION', $action));
        return $this;
    }

    public function getAction(): ?string
    {
        $prop = $this->getProperty('ACTION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function setTrigger(string $trigger): self
    {
        $this->removeProperty('TRIGGER');
        $this->addProperty(GenericProperty::create('TRIGGER', $trigger));
        return $this;
    }

    public function getTrigger(): ?string
    {
        $prop = $this->getProperty('TRIGGER');
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

    public function setRepeat(int $repeat): self
    {
        $this->removeProperty('REPEAT');
        $this->addProperty(GenericProperty::create('REPEAT', (string) $repeat));
        return $this;
    }

    public function getRepeat(): ?int
    {
        $prop = $this->getProperty('REPEAT');
        if ($prop === null) {
            return null;
        }
        $value = $prop->getValue()->getRawValue();
        return is_numeric($value) ? (int) $value : null;
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

    public function setAttendee(string $attendee): self
    {
        $this->removeProperty('ATTENDEE');
        $this->addProperty(GenericProperty::create('ATTENDEE', $attendee));
        return $this;
    }

    public function getAttendee(): ?string
    {
        $prop = $this->getProperty('ATTENDEE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function validate(): void
    {
        if ($this->getProperty('ACTION') === null) {
            throw new ValidationException(
                'VALARM must contain an ACTION property',
                self::ERR_MISSING_ACTION
            );
        }

        if ($this->getProperty('TRIGGER') === null) {
            throw new ValidationException(
                'VALARM must contain a TRIGGER property',
                self::ERR_MISSING_TRIGGER
            );
        }
    }
}
