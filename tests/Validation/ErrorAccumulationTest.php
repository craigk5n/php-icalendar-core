<?php

declare(strict_types=1);

namespace Icalendar\Tests\Validation;

use Icalendar\Component\VAlarm;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Property\GenericProperty;
use Icalendar\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Validation errors must accumulate across every component in a calendar.
 *
 * validateCalendarComponents() and validateSubComponents() recursed through the
 * public validateComponent(), which routes to validateSingleComponent() -- and
 * that resets the error buffer, being the standalone entry point. So each
 * component validated wiped everything found before it, and only the last
 * component's errors survived.
 *
 * The effect was not limited to losing duplicates of the same error: because the
 * VCALENDAR's own PRODID/VERSION checks run before its components, *any*
 * component at all erased them, and isValid() returned true for a calendar
 * missing both required properties.
 *
 * Recursion now goes through doValidateComponent(), which appends rather than
 * resets; validateSingleComponent() keeps its reset for standalone callers.
 */
class ErrorAccumulationTest extends TestCase
{
    private function invalidEvent(string $summary): VEvent
    {
        // No UID and no DTSTAMP: two errors per event.
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('SUMMARY', $summary));

        return $event;
    }

    public function testErrorsFromEveryComponentAreReported(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN')->setVersion('2.0');
        $calendar->addComponent($this->invalidEvent('first'));
        $calendar->addComponent($this->invalidEvent('second'));

        $errors = (new Validator())->validate($calendar)->getErrors();

        // Two events x (missing DTSTAMP + missing UID). Previously only the
        // last event's two errors survived.
        self::assertCount(4, $errors);
    }

    public function testCalendarLevelErrorsSurviveComponentValidation(): void
    {
        // PRODID and VERSION are both missing and REQUIRED (RFC 5545 §3.6).
        $calendar = new VCalendar();
        $calendar->addComponent($this->invalidEvent('any'));

        $result = (new Validator())->validate($calendar);
        $codes = $result->getErrorCodes();

        self::assertContains('ICAL-COMP-001', $codes, 'missing PRODID must still be reported');
        self::assertContains('ICAL-COMP-002', $codes, 'missing VERSION must still be reported');
        self::assertFalse((new Validator())->isValid($calendar));
    }

    public function testSubComponentErrorsDoNotEraseParentErrors(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN')->setVersion('2.0');

        $event = $this->invalidEvent('with alarm');
        // A VALARM missing both ACTION and TRIGGER.
        $event->addComponent(new VAlarm());
        $calendar->addComponent($event);

        $codes = (new Validator())->validate($calendar)->getErrorCodes();

        self::assertContains('ICAL-VEVENT-001', $codes, "parent's missing DTSTAMP survives alarm validation");
        self::assertContains('ICAL-VEVENT-002', $codes, "parent's missing UID survives alarm validation");
        self::assertContains('ICAL-ALARM-001', $codes, "alarm's own errors are reported too");
    }

    /** A standalone component check still starts from a clean buffer. */
    public function testValidateSingleComponentStillResets(): void
    {
        $validator = new Validator();

        $validator->validateSingleComponent($this->invalidEvent('one'));
        $second = $validator->validateSingleComponent($this->invalidEvent('two'));

        self::assertCount(2, $second->getErrors());
    }
}
