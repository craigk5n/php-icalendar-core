<?php

declare(strict_types=1);

namespace Icalendar\Tests\Exception;

use Icalendar\Exception\InvalidDataException;
use Icalendar\Exception\ParseException;
use Icalendar\Exception\ValidationException;
use Icalendar\Validation\ErrorSeverity;
use Icalendar\Validation\ValidationError;
use PHPUnit\Framework\TestCase;

/**
 * Tests for exception classes
 */
class ExceptionTest extends TestCase
{
    public function testParseExceptionStoresContext(): void
    {
        $exception = new ParseException(
            'Invalid property format',
            ParseException::ERR_INVALID_PROPERTY_FORMAT,
            42,
            'INVALID-LINE-HERE'
        );

        $this->assertEquals(ParseException::ERR_INVALID_PROPERTY_FORMAT, $exception->getErrorCode());
        $this->assertEquals(42, $exception->getContentLineNumber());
        $this->assertEquals('INVALID-LINE-HERE', $exception->getContentLine());
        $this->assertEquals('Invalid property format', $exception->getMessage());
    }

    public function testParseExceptionWithDefaults(): void
    {
        $exception = new ParseException(
            'Simple error',
            ParseException::ERR_INVALID_LINE_ENDING
        );

        $this->assertEquals(0, $exception->getContentLineNumber());
        $this->assertNull($exception->getContentLine());
    }

    public function testParseExceptionWithPrevious(): void
    {
        $previous = new \Exception('Original error');
        $exception = new ParseException(
            'Wrapped error',
            ParseException::ERR_MALFORMED_FOLDING,
            10,
            'line content',
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testValidationException(): void
    {
        $exception = new ValidationException(
            'Missing required property',
            ValidationException::ERR_MISSING_PRODID
        );

        $this->assertEquals(ValidationException::ERR_MISSING_PRODID, $exception->getErrorCode());
        $this->assertEquals('Missing required property', $exception->getMessage());
    }

    public function testValidationExceptionWithPrevious(): void
    {
        $previous = new \Exception('Original');
        $exception = new ValidationException(
            'Wrapped',
            ValidationException::ERR_VEVENT_MISSING_UID,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidDataException(): void
    {
        $exception = new InvalidDataException(
            'Invalid data provided',
            InvalidDataException::ERR_WRITE_LINE_ENDING
        );

        $this->assertEquals(InvalidDataException::ERR_WRITE_LINE_ENDING, $exception->getErrorCode());
        $this->assertEquals('Invalid data provided', $exception->getMessage());
    }

    public function testValidationErrorSeverity(): void
    {
        $error = new ValidationError(
            'ICAL-COMP-001',
            'Missing PRODID',
            'VCALENDAR',
            null,
            null,
            5,
            ErrorSeverity::ERROR
        );

        $this->assertEquals('ICAL-COMP-001', $error->code);
        $this->assertEquals('Missing PRODID', $error->message);
        $this->assertEquals('VCALENDAR', $error->component);
        $this->assertNull($error->property);
        $this->assertEquals(5, $error->lineNumber);
        $this->assertEquals(ErrorSeverity::ERROR, $error->severity);
    }

    public function testValidationErrorWithProperty(): void
    {
        $error = new ValidationError(
            'ICAL-VEVENT-001',
            'Missing DTSTAMP',
            'VEVENT',
            'DTSTAMP',
            'BEGIN:VEVENT',
            10,
            ErrorSeverity::FATAL
        );

        $this->assertEquals('DTSTAMP', $error->property);
        $this->assertEquals('BEGIN:VEVENT', $error->line);
        $this->assertEquals(ErrorSeverity::FATAL, $error->severity);
    }

    public function testAllParseExceptionErrorCodesDefined(): void
    {
        $expectedCodes = [
            'ICAL-PARSE-001' => ParseException::ERR_INVALID_LINE_ENDING,
            'ICAL-PARSE-002' => ParseException::ERR_MALFORMED_FOLDING,
            'ICAL-PARSE-003' => ParseException::ERR_LINE_TOO_LONG,
            'ICAL-PARSE-004' => ParseException::ERR_UTF8_SEQUENCE_BROKEN,
            'ICAL-PARSE-005' => ParseException::ERR_BINARY_CORRUPTION,
            'ICAL-PARSE-006' => ParseException::ERR_INVALID_PROPERTY_FORMAT,
            'ICAL-PARSE-007' => ParseException::ERR_INVALID_PROPERTY_NAME,
            'ICAL-PARSE-008' => ParseException::ERR_INVALID_PARAMETER_FORMAT,
            'ICAL-PARSE-009' => ParseException::ERR_UNCLOSED_QUOTED_STRING,
            'ICAL-PARSE-010' => ParseException::ERR_INVALID_RFC6868_ENCODING,
            'ICAL-PARSE-011' => ParseException::ERR_INVALID_MULTI_VALUE_PARAM,
            'ICAL-PARSE-012' => ParseException::ERR_TYPE_DECLARATION_MISMATCH,
            'ICAL-TYPE-001' => ParseException::ERR_INVALID_BINARY,
            'ICAL-TYPE-002' => ParseException::ERR_INVALID_BOOLEAN,
            'ICAL-TYPE-003' => ParseException::ERR_INVALID_CAL_ADDRESS,
            'ICAL-TYPE-004' => ParseException::ERR_INVALID_DATE,
            'ICAL-TYPE-005' => ParseException::ERR_INVALID_DATE_TIME,
            'ICAL-TYPE-006' => ParseException::ERR_INVALID_DURATION,
            'ICAL-TYPE-007' => ParseException::ERR_INVALID_FLOAT,
            'ICAL-TYPE-008' => ParseException::ERR_INVALID_INTEGER,
            'ICAL-TYPE-009' => ParseException::ERR_INVALID_PERIOD,
            'ICAL-TYPE-010' => ParseException::ERR_INVALID_RECUR,
            'ICAL-TYPE-011' => ParseException::ERR_INVALID_TEXT,
            'ICAL-TYPE-012' => ParseException::ERR_INVALID_TIME,
            'ICAL-TYPE-013' => ParseException::ERR_INVALID_URI,
            'ICAL-TYPE-014' => ParseException::ERR_INVALID_UTC_OFFSET,
            'ICAL-IO-001' => ParseException::ERR_FILE_NOT_FOUND,
            'ICAL-IO-002' => ParseException::ERR_FILE_WRITE,
            'ICAL-IO-003' => ParseException::ERR_PERMISSION_DENIED,
        ];

        foreach ($expectedCodes as $expectedValue => $actualConstant) {
            $this->assertEquals($expectedValue, $actualConstant);
        }
    }

    public function testAllValidationExceptionErrorCodesDefined(): void
    {
        $expectedCodes = [
            'ICAL-COMP-001' => ValidationException::ERR_MISSING_PRODID,
            'ICAL-COMP-002' => ValidationException::ERR_MISSING_VERSION,
            'ICAL-COMP-003' => ValidationException::ERR_INVALID_CALSCALE,
            'ICAL-COMP-004' => ValidationException::ERR_INVALID_METHOD,
            'ICAL-COMP-005' => ValidationException::ERR_INVALID_X_PROPERTY,
            'ICAL-VEVENT-001' => ValidationException::ERR_VEVENT_MISSING_DTSTAMP,
            'ICAL-VEVENT-002' => ValidationException::ERR_VEVENT_MISSING_UID,
            'ICAL-VEVENT-VAL-001' => ValidationException::ERR_VEVENT_DTEND_DURATION_EXCLUSIVE,
            'ICAL-VEVENT-VAL-002' => ValidationException::ERR_VEVENT_DATE_VALUE_MISMATCH,
            'ICAL-VEVENT-VAL-003' => ValidationException::ERR_VEVENT_INVALID_STATUS,
            'ICAL-TZ-001' => ValidationException::ERR_TIMEZONE_MISSING_TZID,
            'ICAL-TZ-002' => ValidationException::ERR_TIMEZONE_MISSING_OBSERVANCE,
            'ICAL-ALARM-001' => ValidationException::ERR_ALARM_MISSING_ACTION,
            'ICAL-ALARM-002' => ValidationException::ERR_ALARM_MISSING_TRIGGER,
            'ICAL-ALARM-003' => ValidationException::ERR_ALARM_DISPLAY_MISSING_DESC,
            'ICAL-ALARM-004' => ValidationException::ERR_ALARM_EMAIL_MISSING_PROPS,
            'ICAL-RRULE-001' => ValidationException::ERR_RRULE_INVALID_FREQ,
            'ICAL-RRULE-002' => ValidationException::ERR_RRULE_INVALID_INTERVAL,
            'ICAL-RRULE-003' => ValidationException::ERR_RRULE_INVALID_TERMINATION,
            'ICAL-RRULE-004' => ValidationException::ERR_RRULE_INVALID_BY_PART,
            'ICAL-RRULE-005' => ValidationException::ERR_RRULE_INVALID_WKST,
            'ICAL-RRULE-006' => ValidationException::ERR_RRULE_GENERATION_FAILED,
            'ICAL-RRULE-007' => ValidationException::ERR_RRULE_TIMEZONE_ERROR,
            'ICAL-RRULE-008' => ValidationException::ERR_RRULE_EXDATE_ERROR,
        ];

        foreach ($expectedCodes as $expectedValue => $actualConstant) {
            $this->assertEquals($expectedValue, $actualConstant);
        }
    }

    public function testAllInvalidDataExceptionErrorCodesDefined(): void
    {
        $expectedCodes = [
            'ICAL-WRITE-001' => InvalidDataException::ERR_WRITE_LINE_ENDING,
            'ICAL-WRITE-002' => InvalidDataException::ERR_WRITE_FOLDING,
            'ICAL-WRITE-003' => InvalidDataException::ERR_WRITE_UTF8_SPLIT,
            'ICAL-WRITE-004' => InvalidDataException::ERR_WRITE_BOUNDARY,
            'ICAL-WRITE-005' => InvalidDataException::ERR_WRITE_SERIALIZATION,
            'ICAL-WRITE-006' => InvalidDataException::ERR_WRITE_TEXT_ESCAPE,
            'ICAL-WRITE-007' => InvalidDataException::ERR_WRITE_PARAM_QUOTE,
            'ICAL-WRITE-008' => InvalidDataException::ERR_WRITE_RFC6868,
            'ICAL-WRITE-009' => InvalidDataException::ERR_WRITE_BINARY_WRAP,
            'ICAL-WRITE-010' => InvalidDataException::ERR_WRITE_DATETIME,
            'ICAL-WRITE-011' => InvalidDataException::ERR_WRITE_MISSING_PRODID,
            'ICAL-WRITE-012' => InvalidDataException::ERR_WRITE_MISSING_VERSION,
            'ICAL-WRITE-013' => InvalidDataException::ERR_WRITE_MISSING_REQUIRED,
            'ICAL-SEC-001' => InvalidDataException::ERR_MAX_DEPTH_EXCEEDED,
            'ICAL-SEC-002' => InvalidDataException::ERR_XXE_DETECTED,
            'ICAL-SEC-003' => InvalidDataException::ERR_SSRF_ATTEMPT,
        ];

        foreach ($expectedCodes as $expectedValue => $actualConstant) {
            $this->assertEquals($expectedValue, $actualConstant);
        }
    }

    public function testExceptionSerialization(): void
    {
        $exception = new ParseException(
            'Test error',
            ParseException::ERR_INVALID_DATE,
            100,
            'DTSTART:20241301'
        );

        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ParseException::class, $unserialized);
        $this->assertEquals($exception->getMessage(), $unserialized->getMessage());
        $this->assertEquals($exception->getErrorCode(), $unserialized->getErrorCode());
        $this->assertEquals($exception->getContentLineNumber(), $unserialized->getContentLineNumber());
        $this->assertEquals($exception->getContentLine(), $unserialized->getContentLine());
    }

    public function testValidationExceptionSerialization(): void
    {
        $exception = new ValidationException(
            'Validation failed',
            ValidationException::ERR_MISSING_PRODID
        );

        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ValidationException::class, $unserialized);
        $this->assertEquals($exception->getMessage(), $unserialized->getMessage());
        $this->assertEquals($exception->getErrorCode(), $unserialized->getErrorCode());
    }

    public function testInvalidDataExceptionSerialization(): void
    {
        $exception = new InvalidDataException(
            'Invalid data',
            InvalidDataException::ERR_MAX_DEPTH_EXCEEDED
        );

        $serialized = serialize($exception);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(InvalidDataException::class, $unserialized);
        $this->assertEquals($exception->getMessage(), $unserialized->getMessage());
        $this->assertEquals($exception->getErrorCode(), $unserialized->getErrorCode());
    }

    public function testValidationErrorSerialization(): void
    {
        $error = new ValidationError(
            'ICAL-COMP-001',
            'Test error',
            'VCALENDAR',
            'PRODID',
            'BEGIN:VCALENDAR',
            1,
            ErrorSeverity::WARNING
        );

        $serialized = serialize($error);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ValidationError::class, $unserialized);
        $this->assertEquals($error->code, $unserialized->code);
        $this->assertEquals($error->message, $unserialized->message);
        $this->assertEquals($error->component, $unserialized->component);
        $this->assertEquals($error->property, $unserialized->property);
        $this->assertEquals($error->line, $unserialized->line);
        $this->assertEquals($error->lineNumber, $unserialized->lineNumber);
        $this->assertEquals($error->severity, $unserialized->severity);
    }

    public function testValidationErrorToArrayAndFromArray(): void
    {
        $error = new ValidationError(
            'ICAL-COMP-002',
            'Missing VERSION',
            'VCALENDAR',
            'VERSION',
            'PRODID:-//Test//EN',
            2,
            ErrorSeverity::ERROR
        );

        $array = $error->toArray();

        $this->assertEquals('ICAL-COMP-002', $array['code']);
        $this->assertEquals('Missing VERSION', $array['message']);
        $this->assertEquals('VCALENDAR', $array['component']);
        $this->assertEquals('VERSION', $array['property']);
        $this->assertEquals('PRODID:-//Test//EN', $array['line']);
        $this->assertEquals(2, $array['lineNumber']);
        $this->assertEquals('ERROR', $array['severity']);

        $restored = ValidationError::fromArray($array);

        $this->assertEquals($error->code, $restored->code);
        $this->assertEquals($error->message, $restored->message);
        $this->assertEquals($error->component, $restored->component);
        $this->assertEquals($error->property, $restored->property);
        $this->assertEquals($error->line, $restored->line);
        $this->assertEquals($error->lineNumber, $restored->lineNumber);
        $this->assertEquals($error->severity, $restored->severity);
    }

    public function testErrorSeverityEnumValues(): void
    {
        $this->assertEquals('WARNING', ErrorSeverity::WARNING->value);
        $this->assertEquals('ERROR', ErrorSeverity::ERROR->value);
        $this->assertEquals('FATAL', ErrorSeverity::FATAL->value);
    }
}
