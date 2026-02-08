<?php

declare(strict_types=1);

namespace Icalendar\Exception;

/**
 * Exception thrown during validation operations
 */
class ValidationException extends \Exception
{
    /** Component error codes */
    public const ERR_MISSING_PRODID = 'ICAL-COMP-001';
    public const ERR_MISSING_VERSION = 'ICAL-COMP-002';
    public const ERR_INVALID_CALSCALE = 'ICAL-COMP-003';
    public const ERR_INVALID_METHOD = 'ICAL-COMP-004';
    public const ERR_INVALID_X_PROPERTY = 'ICAL-COMP-005';

    /** VEVENT error codes */
    public const ERR_VEVENT_MISSING_DTSTAMP = 'ICAL-VEVENT-001';
    public const ERR_VEVENT_MISSING_UID = 'ICAL-VEVENT-002';
    public const ERR_VEVENT_DTEND_DURATION_EXCLUSIVE = 'ICAL-VEVENT-VAL-001';
    public const ERR_VEVENT_DATE_VALUE_MISMATCH = 'ICAL-VEVENT-VAL-002';
    public const ERR_VEVENT_INVALID_STATUS = 'ICAL-VEVENT-VAL-003';

    /** VTIMEZONE error codes */
    public const ERR_TIMEZONE_MISSING_TZID = 'ICAL-TZ-001';
    public const ERR_TIMEZONE_MISSING_OBSERVANCE = 'ICAL-TZ-002';

    /** Timezone observance error codes */
    public const ERR_TZ_OBSERVANCE_MISSING_DTSTART = 'ICAL-TZ-OBS-001';
    public const ERR_TZ_OBSERVANCE_MISSING_TZOFFSETTO = 'ICAL-TZ-OBS-002';
    public const ERR_TZ_OBSERVANCE_MISSING_TZOFFSETFROM = 'ICAL-TZ-OBS-003';

    /** VALARM error codes */
    public const ERR_ALARM_MISSING_ACTION = 'ICAL-ALARM-001';
    public const ERR_ALARM_MISSING_TRIGGER = 'ICAL-ALARM-002';
    public const ERR_ALARM_DISPLAY_MISSING_DESC = 'ICAL-ALARM-003';
    public const ERR_ALARM_EMAIL_MISSING_PROPS = 'ICAL-ALARM-004';

    /** PARTICIPANT error codes */
    public const ERR_PARTICIPANT_MISSING_TYPE = 'ICAL-PART-001';

    /** RRULE error codes */
    public const ERR_RRULE_INVALID_FREQ = 'ICAL-RRULE-001';
    public const ERR_RRULE_INVALID_INTERVAL = 'ICAL-RRULE-002';
    public const ERR_RRULE_INVALID_TERMINATION = 'ICAL-RRULE-003';
    public const ERR_RRULE_INVALID_BY_PART = 'ICAL-RRULE-004';
    public const ERR_RRULE_INVALID_WKST = 'ICAL-RRULE-005';
    public const ERR_RRULE_GENERATION_FAILED = 'ICAL-RRULE-006';
    public const ERR_RRULE_TIMEZONE_ERROR = 'ICAL-RRULE-007';
    public const ERR_RRULE_EXDATE_ERROR = 'ICAL-RRULE-008';

    public function __construct(
        string $message,
        private readonly string $errorCode,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $this->errorCodeToCode($errorCode), $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    private function errorCodeToCode(string $errorCode): int
    {
        // Convert string error codes to unique integers for PHPUnit
        return crc32($errorCode) & 0x7fffffff; // Ensure positive
    }
}
