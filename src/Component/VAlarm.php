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
    public const ERR_DISPLAY_MISSING_DESCRIPTION = 'ICAL-ALARM-003';
    public const ERR_EMAIL_MISSING_PROPERTIES = 'ICAL-ALARM-004';
    public const ERR_REPEAT_DURATION_MISMATCH = 'ICAL-ALARM-VAL-001';
    public const ERR_INVALID_ACTION = 'ICAL-ALARM-VAL-002';

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

    public function setAttach(string $attach): self
    {
        $this->removeProperty('ATTACH');
        $this->addProperty(GenericProperty::create('ATTACH', $attach));
        return $this;
    }

    public function getAttach(): ?string
    {
        $prop = $this->getProperty('ATTACH');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    public function validate(): void
    {
        // Check required ACTION property
        if ($this->getProperty('ACTION') === null) {
            throw new ValidationException(
                'VALARM must contain an ACTION property',
                self::ERR_MISSING_ACTION
            );
        }

        // Check required TRIGGER property
        if ($this->getProperty('TRIGGER') === null) {
            throw new ValidationException(
                'VALARM must contain a TRIGGER property',
                self::ERR_MISSING_TRIGGER
            );
        }

        // Validate ACTION value
        $action = $this->getAction();
        $validActions = [self::ACTION_AUDIO, self::ACTION_DISPLAY, self::ACTION_EMAIL];
        if (!in_array($action, $validActions, true)) {
            throw new ValidationException(
                "Invalid ACTION: {$action}. Must be AUDIO, DISPLAY, or EMAIL",
                self::ERR_INVALID_ACTION
            );
        }

        // Validate action-specific requirements
        $this->validateActionRequirements($action);

        // Validate REPEAT and DURATION mutual requirement
        $this->validateRepeatDuration();
    }

    /**
     * Validate action-specific property requirements
     */
    private function validateActionRequirements(string $action): void
    {
        switch ($action) {
            case self::ACTION_DISPLAY:
                // DISPLAY requires DESCRIPTION
                if ($this->getProperty('DESCRIPTION') === null) {
                    throw new ValidationException(
                        'VALARM with ACTION=DISPLAY must contain a DESCRIPTION property',
                        self::ERR_DISPLAY_MISSING_DESCRIPTION
                    );
                }
                break;

            case self::ACTION_EMAIL:
                // EMAIL requires SUMMARY, DESCRIPTION, and at least one ATTENDEE
                $missing = [];
                if ($this->getProperty('SUMMARY') === null) {
                    $missing[] = 'SUMMARY';
                }
                if ($this->getProperty('DESCRIPTION') === null) {
                    $missing[] = 'DESCRIPTION';
                }
                if ($this->getProperty('ATTENDEE') === null) {
                    $missing[] = 'ATTENDEE';
                }
                if (!empty($missing)) {
                    throw new ValidationException(
                        'VALARM with ACTION=EMAIL must contain ' . implode(', ', $missing) . ' properties',
                        self::ERR_EMAIL_MISSING_PROPERTIES
                    );
                }
                break;

            case self::ACTION_AUDIO:
                // AUDIO has optional ATTACH, no additional requirements
                break;
        }
    }

    /**
     * Validate that REPEAT and DURATION are both present or both absent
     */
    private function validateRepeatDuration(): void
    {
        $hasRepeat = $this->getProperty('REPEAT') !== null;
        $hasDuration = $this->getProperty('DURATION') !== null;

        if ($hasRepeat !== $hasDuration) {
            throw new ValidationException(
                'VALARM REPEAT and DURATION must both be present or both absent',
                self::ERR_REPEAT_DURATION_MISMATCH
            );
        }
    }
}
