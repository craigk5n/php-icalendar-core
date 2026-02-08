<?php

declare(strict_types=1);

namespace Icalendar\Validation;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VTodo;
use Icalendar\Component\VJournal;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VTimezone;
use Icalendar\Component\Standard;
use Icalendar\Component\Daylight;
use Icalendar\Component\VAlarm;
use Icalendar\Property\PropertyInterface;
use Icalendar\Exception\ValidationException;

/**
 * Main validator class for iCalendar components
 *
 * Provides comprehensive validation including:
 * - Required property validation
 * - Mutual exclusivity constraints
 * - Value range validation
 * - Timezone reference validation
 * - Recurrence rule validation
 * - Cross-component validation
 */
class Validator
{
    /** @var ValidationError[] */
    private array $errors = [];

    /** @var VTimezone[] */
    private array $timezones = [];

    public function validate(VCalendar $calendar): array
    {
        $this->errors = [];
        $this->timezones = [];

        $this->collectTimezones($calendar);
        $this->validateCalendar($calendar);

        return $this->errors;
    }

    /**
     * Collect all timezones from calendar for reference validation
     */
    private function collectTimezones(VCalendar $calendar): void
    {
        foreach ($calendar->getComponents('VTIMEZONE') as $tz) {
            if ($tz instanceof VTimezone) {
                $tzid = $tz->getProperty('TZID')?->getValue()?->getRawValue();
                if ($tzid !== null) {
                    $this->timezones[$tzid] = $tz;
                }
            }
        }
    }

    /**
     * Validate calendar component
     */
    private function validateCalendar(VCalendar $calendar): void
    {
        $this->validateVCalendarRequiredProperties($calendar);
        $this->validateCalendarComponents($calendar);
    }

    /**
     * Validate VCALENDAR required properties
     */
    private function validateVCalendarRequiredProperties(VCalendar $calendar): void
    {
        if ($calendar->getProperty('PRODID') === null) {
            $this->addError(
                'ICAL-COMP-001',
                'VCALENDAR must contain a PRODID property',
                'VCALENDAR',
                'PRODID',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        if ($calendar->getProperty('VERSION') === null) {
            $this->addError(
                'ICAL-COMP-002',
                'VCALENDAR must contain a VERSION property',
                'VCALENDAR',
                'VERSION',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }
    }

    /**
     * Validate all components in calendar
     */
    private function validateCalendarComponents(VCalendar $calendar): void
    {
        foreach ($calendar->getComponents() as $component) {
            $this->validateComponent($component);
        }
    }

    /**
     * Validate a single component
     */
    private function doValidateComponent(ComponentInterface $component): void
    {
        $name = $component->getName();

        switch ($name) {
            case 'VEVENT':
                $this->validateVEvent($component instanceof VEvent ? $component : throw new \InvalidArgumentException('Expected VEvent'));
                break;
            case 'VTODO':
                $this->validateVTodo($component instanceof VTodo ? $component : throw new \InvalidArgumentException('Expected VTodo'));
                break;
            case 'VJOURNAL':
                $this->validateVJournal($component instanceof VJournal ? $component : throw new \InvalidArgumentException('Expected VJournal'));
                break;
            case 'VFREEBUSY':
                $this->validateVFreeBusy($component instanceof VFreeBusy ? $component : throw new \InvalidArgumentException('Expected VFreeBusy'));
                break;
            case 'VTIMEZONE':
                $this->validateVTimezone($component instanceof VTimezone ? $component : throw new \InvalidArgumentException('Expected VTimezone'));
                break;
            case 'VALARM':
                $this->validateVAlarm($component instanceof VAlarm ? $component : throw new \InvalidArgumentException('Expected VAlarm'));
                break;
        }
    }

    /**
     * Validate a single component and return errors
     */
    public function validateSingleComponent(ComponentInterface $component): array
    {
        $this->errors = [];
        $this->doValidateComponent($component);
        return $this->errors;
    }

    /**
     * Validate a component (backward compatibility)
     */
    public function validateComponent(ComponentInterface $component): array
    {
        return $this->validateSingleComponent($component);
    }

    /**
     * Validate VEVENT component
     */
    private function validateVEvent(VEvent $event): void
    {
        $this->validateRequiredProperty($event, 'DTSTAMP', 'ICAL-VEVENT-001');
        $this->validateRequiredProperty($event, 'UID', 'ICAL-VEVENT-002');

        $hasDtEnd = $event->getProperty('DTEND') !== null;
        $hasDuration = $event->getProperty('DURATION') !== null;

        if ($hasDtEnd && $hasDuration) {
            $this->addError(
                'ICAL-VEVENT-VAL-001',
                'VEVENT cannot have both DTEND and DURATION properties',
                'VEVENT',
                'DTEND',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        $this->validateDateValueConsistency($event);

        $this->validateStatus($event);

        $this->validateTimezoneReferences($event);

        $this->validateRecurrenceRules($event);

        $this->validateSubComponents($event);
    }

    /**
     * Validate VTODO component
     */
    private function validateVTodo(VTodo $todo): void
    {
        $this->validateRequiredProperty($todo, 'DTSTAMP', 'ICAL-VTODO-001');
        $this->validateRequiredProperty($todo, 'UID', 'ICAL-VTODO-002');

        $hasDue = $todo->getProperty('DUE') !== null;
        $hasDuration = $todo->getProperty('DURATION') !== null;

        if ($hasDue && $hasDuration) {
            $this->addError(
                'ICAL-VTODO-VAL-001',
                'VTODO cannot have both DUE and DURATION properties',
                'VTODO',
                'DUE',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        $this->validateVTodoStatus($todo);

        $this->validateTimezoneReferences($todo);

        $this->validateSubComponents($todo);
    }

    /**
     * Validate VJOURNAL component
     */
    private function validateVJournal(VJournal $journal): void
    {
        $this->validateRequiredProperty($journal, 'DTSTAMP', 'ICAL-VJOURNAL-001');
        $this->validateRequiredProperty($journal, 'UID', 'ICAL-VJOURNAL-002');

        $this->validateVJournalStatus($journal);

        $this->validateSubComponents($journal);
    }

    /**
     * Validate VFREEBUSY component
     */
    private function validateVFreeBusy(VFreeBusy $freebusy): void
    {
        $this->validateRequiredProperty($freebusy, 'DTSTAMP', 'ICAL-VFB-001');
        $this->validateRequiredProperty($freebusy, 'UID', 'ICAL-VFB-002');

        $this->validateSubComponents($freebusy);
    }

    /**
     * Validate VTIMEZONE component
     */
    private function validateVTimezone(VTimezone $tz): void
    {
        if ($tz->getProperty('TZID') === null) {
            $this->addError(
                'ICAL-TZ-001',
                'VTIMEZONE must contain a TZID property',
                'VTIMEZONE',
                'TZID',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        $standardCount = count($tz->getComponents('STANDARD'));
        $daylightCount = count($tz->getComponents('DAYLIGHT'));

        if ($standardCount === 0 && $daylightCount === 0) {
            $this->addError(
                'ICAL-TZ-002',
                'VTIMEZONE must contain at least one STANDARD or DAYLIGHT sub-component',
                'VTIMEZONE',
                null,
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        foreach ($tz->getComponents('STANDARD') as $standard) {
            if ($standard instanceof Standard) {
                $this->validateObservance($standard);
            }
        }

        foreach ($tz->getComponents('DAYLIGHT') as $daylight) {
            if ($daylight instanceof Daylight) {
                $this->validateObservance($daylight);
            }
        }
    }

    /**
     * Validate observance component (STANDARD or DAYLIGHT)
     */
    private function validateObservance(ComponentInterface $observance): void
    {
        $name = $observance->getName();

        if ($observance->getProperty('DTSTART') === null) {
            $this->addError(
                'ICAL-TZ-OBS-001',
                "{$name} must contain a DTSTART property",
                $name,
                'DTSTART',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        if ($observance->getProperty('TZOFFSETTO') === null) {
            $this->addError(
                'ICAL-TZ-OBS-002',
                "{$name} must contain a TZOFFSETTO property",
                $name,
                'TZOFFSETTO',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        if ($observance->getProperty('TZOFFSETFROM') === null) {
            $this->addError(
                'ICAL-TZ-OBS-003',
                "{$name} must contain a TZOFFSETFROM property",
                $name,
                'TZOFFSETFROM',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }
    }

    /**
     * Validate VALARM component
     */
    private function validateVAlarm(VAlarm $alarm): void
    {
        $action = $alarm->getProperty('ACTION')?->getValue()?->getRawValue();

        if ($action === null) {
            $this->addError(
                'ICAL-ALARM-001',
                'VALARM must contain an ACTION property',
                'VALARM',
                'ACTION',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        $trigger = $alarm->getProperty('TRIGGER');
        if ($trigger === null) {
            $this->addError(
                'ICAL-ALARM-002',
                'VALARM must contain a TRIGGER property',
                'VALARM',
                'TRIGGER',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        if ($action !== null && !in_array($action, ['AUDIO', 'DISPLAY', 'EMAIL'], true)) {
            $this->addError(
                'ICAL-ALARM-VAL-002',
                "VALARM ACTION must be AUDIO, DISPLAY, or EMAIL, got: {$action}",
                'VALARM',
                'ACTION',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        if ($action === 'DISPLAY' && $alarm->getProperty('DESCRIPTION') === null) {
            $this->addError(
                'ICAL-ALARM-003',
                'VALARM with DISPLAY action must have a DESCRIPTION property',
                'VALARM',
                'DESCRIPTION',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        if ($action === 'EMAIL') {
            $hasSummary = $alarm->getProperty('SUMMARY') !== null;
            $hasDescription = $alarm->getProperty('DESCRIPTION') !== null;
            $hasAttendee = $alarm->getProperty('ATTENDEE') !== null;

            if (!$hasSummary || !$hasDescription || !$hasAttendee) {
                $this->addError(
                    'ICAL-ALARM-004',
                    'VALARM with EMAIL action must have SUMMARY, DESCRIPTION, and ATTENDEE properties',
                    'VALARM',
                    null,
                    null,
                    0,
                    ErrorSeverity::ERROR
                );
            }
        }

        $hasRepeat = $alarm->getProperty('REPEAT') !== null;
        $hasDuration = $alarm->getProperty('DURATION') !== null;

        if ($hasRepeat && !$hasDuration) {
            $this->addError(
                'ICAL-ALARM-VAL-001',
                'VALARM REPEAT must have corresponding DURATION',
                'VALARM',
                'REPEAT',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }

        if (!$hasRepeat && $hasDuration) {
            $this->addError(
                'ICAL-ALARM-VAL-001',
                'VALARM DURATION must have corresponding REPEAT',
                'VALARM',
                'DURATION',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }
    }

    /**
     * Validate required property exists
     */
    private function validateRequiredProperty(ComponentInterface $component, string $propertyName, string $errorCode): void
    {
        if ($component->getProperty($propertyName) === null) {
            $this->addError(
                $errorCode,
                "{$component->getName()} must contain a {$propertyName} property",
                $component->getName(),
                $propertyName,
                null,
                0,
                ErrorSeverity::ERROR
            );
        }
    }

    /**
     * Validate DATE/DATE-TIME consistency
     */
    private function validateDateValueConsistency(VEvent $event): void
    {
        $dtStart = $event->getProperty('DTSTART');
        $dtEnd = $event->getProperty('DTEND');

        if ($dtStart !== null && $dtEnd !== null) {
            $startParams = $dtStart->getParameters();
            $endParams = $dtEnd->getParameters();

            $startHasTime = !isset($startParams['VALUE']) || $startParams['VALUE'] !== 'DATE';
            $endHasTime = !isset($endParams['VALUE']) || $endParams['VALUE'] !== 'DATE';

            if ($startHasTime !== $endHasTime) {
                $this->addError(
                    'ICAL-VEVENT-VAL-002',
                    'VEVENT DTSTART and DTEND must both be DATE or both be DATE-TIME',
                    'VEVENT',
                    'DTEND',
                    null,
                    0,
                    ErrorSeverity::ERROR
                );
            }
        }
    }

    /**
     * Validate VEVENT STATUS values
     */
    private function validateStatus(VEvent $event): void
    {
        $status = $event->getProperty('STATUS');
        if ($status !== null) {
            $statusValue = $status->getValue()->getRawValue();
            $validStatuses = ['TENTATIVE', 'CONFIRMED', 'CANCELLED'];

            if (!in_array($statusValue, $validStatuses, true)) {
                $this->addError(
                    'ICAL-VEVENT-VAL-003',
                    "VEVENT STATUS must be TENTATIVE, CONFIRMED, or CANCELLED, got: {$statusValue}",
                    'VEVENT',
                    'STATUS',
                    null,
                    0,
                    ErrorSeverity::ERROR
                );
            }
        }
    }

    /**
     * Validate VTODO STATUS values
     */
    private function validateVTodoStatus(VTodo $todo): void
    {
        $status = $todo->getProperty('STATUS');
        if ($status !== null) {
            $statusValue = $status->getValue()->getRawValue();
            $validStatuses = ['NEEDS-ACTION', 'COMPLETED', 'IN-PROCESS', 'CANCELLED'];

            if (!in_array($statusValue, $validStatuses, true)) {
                $this->addError(
                    'ICAL-VTODO-VAL-002',
                    "VTODO STATUS must be NEEDS-ACTION, COMPLETED, IN-PROCESS, or CANCELLED, got: {$statusValue}",
                    'VTODO',
                    'STATUS',
                    null,
                    0,
                    ErrorSeverity::ERROR
                );
            }
        }

        $percentComplete = $todo->getProperty('PERCENT-COMPLETE');
        if ($percentComplete !== null) {
            $value = $percentComplete->getValue()->getRawValue();
            if (!ctype_digit($value)) {
                $this->addError(
                    'ICAL-VTODO-VAL-003',
                    'VTODO PERCENT-COMPLETE must be an integer between 0 and 100',
                    'VTODO',
                    'PERCENT-COMPLETE',
                    null,
                    0,
                    ErrorSeverity::ERROR
                );
            }
        }
    }

    /**
     * Validate VJOURNAL STATUS values
     */
    private function validateVJournalStatus(VJournal $journal): void
    {
        $status = $journal->getProperty('STATUS');
        if ($status !== null) {
            $statusValue = $status->getValue()->getRawValue();
            $validStatuses = ['DRAFT', 'FINAL', 'CANCELLED'];

            if (!in_array($statusValue, $validStatuses, true)) {
                $this->addError(
                    'ICAL-VJOURNAL-VAL-001',
                    "VJOURNAL STATUS must be DRAFT, FINAL, or CANCELLED, got: {$statusValue}",
                    'VJOURNAL',
                    'STATUS',
                    null,
                    0,
                    ErrorSeverity::ERROR
                );
            }
        }
    }

    /**
     * Validate timezone references in component
     */
    private function validateTimezoneReferences(ComponentInterface $component): void
    {
        foreach ($component->getProperties() as $property) {
            $params = $property->getParameters();
            if (isset($params['TZID'])) {
                $tzid = $params['TZID'];
                if (!isset($this->timezones[$tzid])) {
                    $this->addError(
                        'ICAL-VAL-TZ-001',
                        "Component references undefined timezone: {$tzid}",
                        $component->getName(),
                        $property->getName(),
                        null,
                        0,
                        ErrorSeverity::WARNING
                    );
                }
            }
        }
    }

    /**
     * Validate recurrence rules
     */
    private function validateRecurrenceRules(ComponentInterface $component): void
    {
        $rrule = $component->getProperty('RRULE');
        if ($rrule !== null) {
            $rruleValue = $rrule->getValue()->getRawValue();
            $this->validateRRuleFormat($rruleValue, $component);
        }
    }

    /**
     * Validate RRULE format
     */
    private function validateRRuleFormat(string $rruleValue, ComponentInterface $component): void
    {
        $parts = explode(';', $rruleValue);

        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) < 2) {
                continue;
            }
            [$key, $value] = $kv;

            switch ($key) {
                case 'FREQ':
                    $validFreqs = ['SECONDLY', 'MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];
                    if (!in_array($value, $validFreqs, true)) {
                        $this->addError(
                            'ICAL-RRULE-001',
                            "RRULE FREQ must be SECONDLY, MINUTELY, HOURLY, DAILY, WEEKLY, MONTHLY, or YEARLY, got: {$value}",
                            $component->getName(),
                            'RRULE',
                            null,
                            0,
                            ErrorSeverity::ERROR
                        );
                    }
                    break;

                case 'INTERVAL':
                    if (!ctype_digit($value) || (int) $value < 1) {
                        $this->addError(
                            'ICAL-RRULE-002',
                            'RRULE INTERVAL must be a positive integer',
                            $component->getName(),
                            'RRULE',
                            null,
                            0,
                            ErrorSeverity::ERROR
                        );
                    }
                    break;

                case 'UNTIL':
                    if (!preg_match('/^\d{8}T\d{6}Z$/', $value) && !preg_match('/^\d{8}$/', $value)) {
                        $this->addError(
                            'ICAL-RRULE-003',
                            'RRULE UNTIL must be in YYYYMMDDTHHMMSSZ or YYYYMMDD format',
                            $component->getName(),
                            'RRULE',
                            null,
                            0,
                            ErrorSeverity::ERROR
                        );
                    }
                    break;

                case 'COUNT':
                    if (!ctype_digit($value) || (int) $value < 1) {
                        $this->addError(
                            'ICAL-RRULE-003',
                            'RRULE COUNT must be a positive integer',
                            $component->getName(),
                            'RRULE',
                            null,
                            0,
                            ErrorSeverity::ERROR
                        );
                    }
                    break;
            }
        }

        $hasUntil = false;
        $hasCount = false;
        foreach ($parts as $part) {
            if (str_starts_with($part, 'UNTIL=')) {
                $hasUntil = true;
            }
            if (str_starts_with($part, 'COUNT=')) {
                $hasCount = true;
            }
        }

        if ($hasUntil && $hasCount) {
            $this->addError(
                'ICAL-RRULE-003',
                'RRULE cannot have both UNTIL and COUNT',
                $component->getName(),
                'RRULE',
                null,
                0,
                ErrorSeverity::ERROR
            );
        }
    }

    /**
     * Validate sub-components
     */
    private function validateSubComponents(ComponentInterface $component): void
    {
        foreach ($component->getComponents() as $subComponent) {
            $this->validateComponent($subComponent);
        }
    }

    /**
     * Add validation error
     */
    private function addError(
        string $code,
        string $message,
        string $component,
        ?string $property,
        ?string $line,
        int $lineNumber,
        ErrorSeverity $severity
    ): void {
        $this->errors[] = new ValidationError(
            $code,
            $message,
            $component,
            $property,
            $line,
            $lineNumber,
            $severity
        );
    }

    /**
     * Validate a property value
     */
    public function validateProperty(PropertyInterface $property): array
    {
        $this->errors = [];
        $this->validatePropertyValue($property);
        return $this->errors;
    }

    /**
     * Validate property value format
     */
    private function validatePropertyValue(PropertyInterface $property): void
    {
        $name = $property->getName();
        $value = $property->getValue();

        switch ($name) {
            case 'DTSTART':
            case 'DTEND':
            case 'DTSTAMP':
            case 'RECURRENCE-ID':
                $rawValue = $value->getRawValue();
                if (!preg_match('/^\d{8}T\d{6}Z$/', $rawValue) &&
                    !preg_match('/^\d{8}$/', $rawValue)) {
                    $this->addError(
                        'ICAL-VAL-PROP-001',
                        "Property {$name} has invalid DATE-TIME format: {$rawValue}",
                        'UNKNOWN',
                        $name,
                        null,
                        0,
                        ErrorSeverity::ERROR
                    );
                }
                break;
        }
    }

    /**
     * Check if validation passed (no errors)
     */
    public function isValid(VCalendar $calendar): bool
    {
        return empty($this->validate($calendar));
    }

    /**
     * Get count of errors by severity
     */
    public function getErrorCounts(VCalendar $calendar): array
    {
        $errors = $this->validate($calendar);
        $counts = [
            'WARNING' => 0,
            'ERROR' => 0,
            'FATAL' => 0,
        ];

        foreach ($errors as $error) {
            $counts[$error->severity->value]++;
        }

        return $counts;
    }
}
