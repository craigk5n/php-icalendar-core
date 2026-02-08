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

    #[\Override]
    public function getName(): string
    {
        return 'VALARM';
    }

    /**
     * Set the action type for this alarm
     *
     * The ACTION property specifies the type of alarm action to perform.
     * This is a required property for VALARM components.
     *
     * @param string $action The alarm action (must be AUDIO, DISPLAY, or EMAIL)
     * @return self For method chaining
     */
    public function setAction(string $action): self
    {
        $this->removeProperty('ACTION');
        $this->addProperty(GenericProperty::create('ACTION', $action));
        return $this;
    }

    /**
     * Get the action type for this alarm
     *
     * @return string|null The alarm action or null if not set
     */
    public function getAction(): ?string
    {
        $prop = $this->getProperty('ACTION');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the trigger for this alarm
     *
     * The TRIGGER property specifies when the alarm should be triggered.
     * This is a required property for VALARM components.
     *
     * @param string $trigger The trigger specification (e.g., "-PT15M" for 15 minutes before)
     * @return self For method chaining
     */
    public function setTrigger(string $trigger): self
    {
        $this->removeProperty('TRIGGER');
        $this->addProperty(GenericProperty::create('TRIGGER', $trigger));
        return $this;
    }

    /**
     * Get the trigger for this alarm
     *
     * @return string|null The trigger specification or null if not set
     */
    public function getTrigger(): ?string
    {
        $prop = $this->getProperty('TRIGGER');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set the duration for repeating alarms
     *
     * When used with REPEAT, specifies the duration between subsequent alarm triggers.
     * Must be used together with REPEAT or not at all.
     *
     * @param string $duration The duration in ISO 8601 format (e.g., "PT5M" for 5 minutes)
     * @return self For method chaining
     */
    public function setDuration(string $duration): self
    {
        $this->removeProperty('DURATION');
        $this->addProperty(GenericProperty::create('DURATION', $duration));
        return $this;
    }

    /**
     * Get the duration for repeating alarms
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
     * Set the repeat count for repeating alarms
     *
     * Specifies how many times the alarm should repeat after the initial trigger.
     * Must be used together with DURATION or not at all.
     *
     * @param int $repeat The number of times to repeat the alarm
     * @return self For method chaining
     */
    public function setRepeat(int $repeat): self
    {
        $this->removeProperty('REPEAT');
        $this->addProperty(GenericProperty::create('REPEAT', (string) $repeat));
        return $this;
    }

    /**
     * Get the repeat count for repeating alarms
     *
     * @return int|null The repeat count or null if not set
     */
    public function getRepeat(): ?int
    {
        $prop = $this->getProperty('REPEAT');
        if ($prop === null) {
            return null;
        }
        $value = $prop->getValue()->getRawValue();
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Set the description for this alarm
     *
     * Required for DISPLAY action alarms, optional for EMAIL action alarms.
     *
     * @param string $description The alarm description text
     * @return self For method chaining
     */
    public function setDescription(string $description): self
    {
        $this->removeProperty('DESCRIPTION');
        $this->addProperty(GenericProperty::create('DESCRIPTION', $description));
        return $this;
    }

    /**
     * Get the description for this alarm
     *
     * @return string|null The alarm description or null if not set
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
     * Set the summary for this alarm
     *
     * Required for EMAIL action alarms.
     *
     * @param string $summary The alarm summary text
     * @return self For method chaining
     */
    public function setSummary(string $summary): self
    {
        $this->removeProperty('SUMMARY');
        $this->addProperty(GenericProperty::create('SUMMARY', $summary));
        return $this;
    }

    /**
     * Get the summary for this alarm
     *
     * @return string|null The alarm summary or null if not set
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
     * Set an attendee for this alarm
     *
     * Required for EMAIL action alarms. Can be called multiple times for multiple attendees.
     *
     * @param string $attendee The attendee email address (e.g., "mailto:user@example.com")
     * @return self For method chaining
     */
    public function setAttendee(string $attendee): self
    {
        $this->removeProperty('ATTENDEE');
        $this->addProperty(GenericProperty::create('ATTENDEE', $attendee));
        return $this;
    }

    /**
     * Get the attendee for this alarm
     *
     * @return string|null The attendee address or null if not set
     */
    public function getAttendee(): ?string
    {
        $prop = $this->getProperty('ATTENDEE');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Set an attachment for this alarm
     *
     * Used for AUDIO action alarms to specify sound file URL.
     *
     * @param string $attach The attachment URL or data
     * @return self For method chaining
     */
    public function setAttach(string $attach): self
    {
        $this->removeProperty('ATTACH');
        $this->addProperty(GenericProperty::create('ATTACH', $attach));
        return $this;
    }

    /**
     * Get the attachment for this alarm
     *
     * @return string|null The attachment URL or data or null if not set
     */
    public function getAttach(): ?string
    {
        $prop = $this->getProperty('ATTACH');
        if ($prop === null) {
            return null;
        }
        return $prop->getValue()->getRawValue();
    }

    /**
     * Validate this VALARM component against RFC 5545 requirements
     *
     * Ensures that required properties (ACTION and TRIGGER) are present and that
     * action-specific requirements are met. Also validates that REPEAT and DURATION
     * are either both present or both absent.
     *
     * @throws ValidationException If ACTION property is missing (code: ICAL-ALARM-001)
     * @throws ValidationException If TRIGGER property is missing (code: ICAL-ALARM-002)
     * @throws ValidationException If ACTION value is invalid (code: ICAL-ALARM-VAL-002)
     * @throws ValidationException If DISPLAY action lacks DESCRIPTION (code: ICAL-ALARM-003)
     * @throws ValidationException If EMAIL action lacks required properties (code: ICAL-ALARM-004)
     * @throws ValidationException If REPEAT and DURATION don't match (code: ICAL-ALARM-VAL-001)
     * @return void
     */
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
