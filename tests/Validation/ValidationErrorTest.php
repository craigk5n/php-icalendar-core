<?php

declare(strict_types=1);

namespace Icalendar\Tests\Validation;

use Icalendar\Validation\ErrorSeverity;
use Icalendar\Validation\ValidationError;
use PHPUnit\Framework\TestCase;

class ValidationErrorTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $code = 'ICAL-COMP-001';
        $message = 'Missing PRODID property';
        $component = 'VCALENDAR';
        $property = null;
        $line = null;
        $lineNumber = 0;
        $severity = ErrorSeverity::ERROR;

        $error = new ValidationError(
            $code,
            $message,
            $component,
            $property,
            $line,
            $lineNumber,
            $severity
        );

        $this->assertEquals($code, $error->code);
        $this->assertEquals($message, $error->message);
        $this->assertEquals($component, $error->component);
        $this->assertEquals($property, $error->property);
        $this->assertEquals($line, $error->line);
        $this->assertEquals($lineNumber, $error->lineNumber);
        $this->assertSame($severity, $error->severity);
    }

    public function testConstructorWithProperty(): void
    {
        $code = 'ICAL-VEVENT-001';
        $message = 'Missing DTSTAMP property';
        $component = 'VEVENT';
        $property = 'DTSTAMP';
        $line = 'BEGIN:VEVENT';
        $lineNumber = 10;
        $severity = ErrorSeverity::FATAL;

        $error = new ValidationError(
            $code,
            $message,
            $component,
            $property,
            $line,
            $lineNumber,
            $severity
        );

        $this->assertEquals($property, $error->property);
        $this->assertEquals($line, $error->line);
        $this->assertSame($severity, $error->severity);
    }

    public function testToArray(): void
    {
        $error = new ValidationError(
            'ICAL-TYPE-001',
            'Invalid binary data',
            'ATTACH',
            'ENCODING',
            'ENCODING=BASE64:invalid',
            25,
            ErrorSeverity::WARNING
        );

        $array = $error->toArray();

        $expected = [
            'code' => 'ICAL-TYPE-001',
            'message' => 'Invalid binary data',
            'component' => 'ATTACH',
            'property' => 'ENCODING',
            'line' => 'ENCODING=BASE64:invalid',
            'lineNumber' => 25,
            'severity' => 'WARNING',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testToArrayWithNulls(): void
    {
        $error = new ValidationError(
            'ICAL-VALIDATION',
            'Generic validation error',
            'TEST',
            null,
            null,
            0,
            ErrorSeverity::ERROR
        );

        $array = $error->toArray();

        $expected = [
            'code' => 'ICAL-VALIDATION',
            'message' => 'Generic validation error',
            'component' => 'TEST',
            'property' => null,
            'line' => null,
            'lineNumber' => 0,
            'severity' => 'ERROR',
        ];

        $this->assertEquals($expected, $array);
    }

    public function testFromArray(): void
    {
        $data = [
            'code' => 'ICAL-TIMEZONE-001',
            'message' => 'Missing TZID property',
            'component' => 'VTIMEZONE',
            'property' => 'TZID',
            'line' => 'BEGIN:VTIMEZONE',
            'lineNumber' => 50,
            'severity' => 'FATAL',
        ];

        $error = ValidationError::fromArray($data);

        $this->assertEquals('ICAL-TIMEZONE-001', $error->code);
        $this->assertEquals('Missing TZID property', $error->message);
        $this->assertEquals('VTIMEZONE', $error->component);
        $this->assertEquals('TZID', $error->property);
        $this->assertEquals('BEGIN:VTIMEZONE', $error->line);
        $this->assertEquals(50, $error->lineNumber);
        $this->assertSame(ErrorSeverity::FATAL, $error->severity);
    }

    public function testFromArrayWithMissingOptionalFields(): void
    {
        $data = [
            'code' => 'ICAL-TEST',
            'message' => 'Test error',
            'component' => 'TEST',
            'lineNumber' => 1,
            'severity' => 'WARNING',
        ];

        $error = ValidationError::fromArray($data);

        $this->assertNull($error->property);
        $this->assertNull($error->line);
    }

    public function testFromArrayWithInvalidSeverity(): void
    {
        $this->expectException(\ValueError::class);
        
        $data = [
            'code' => 'ICAL-TEST',
            'message' => 'Test error',
            'component' => 'TEST',
            'lineNumber' => 1,
            'severity' => 'INVALID',
        ];

        ValidationError::fromArray($data);
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(ValidationError::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testErrorWithDifferentSeverities(): void
    {
        $warning = new ValidationError('WARN', 'Warning', 'TEST', null, null, 1, ErrorSeverity::WARNING);
        $error = new ValidationError('ERROR', 'Error', 'TEST', null, null, 1, ErrorSeverity::ERROR);
        $fatal = new ValidationError('FATAL', 'Fatal', 'TEST', null, null, 1, ErrorSeverity::FATAL);

        $this->assertSame(ErrorSeverity::WARNING, $warning->severity);
        $this->assertSame(ErrorSeverity::ERROR, $error->severity);
        $this->assertSame(ErrorSeverity::FATAL, $fatal->severity);
    }

    public function testRoundTripConversion(): void
    {
        $original = new ValidationError(
            'ICAL-RRULE-001',
            'Invalid recurrence rule',
            'RRULE',
            'FREQ',
            'FREQ=INVALID',
            100,
            ErrorSeverity::ERROR
        );

        $array = $original->toArray();
        $restored = ValidationError::fromArray($array);

        $this->assertEquals($original->code, $restored->code);
        $this->assertEquals($original->message, $restored->message);
        $this->assertEquals($original->component, $restored->component);
        $this->assertEquals($original->property, $restored->property);
        $this->assertEquals($original->line, $restored->line);
        $this->assertEquals($original->lineNumber, $restored->lineNumber);
        $this->assertSame($original->severity, $restored->severity);
    }
}