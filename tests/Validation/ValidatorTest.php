<?php

declare(strict_types=1);

namespace Icalendar\Tests\Validation;

use Icalendar\Component\VAlarm;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTimezone;
use Icalendar\Component\Standard;
use Icalendar\Component\VTodo;
use Icalendar\Property\GenericProperty;
use Icalendar\Property\PropertyInterface;
use Icalendar\Value\TextValue;
use Icalendar\Validation\Validator;
use Icalendar\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    private function createProperty(string $name, string $value): PropertyInterface
    {
        return new GenericProperty($name, new TextValue($value));
    }

    public function testValidateEmptyCalendar(): void
    {
        $calendar = new VCalendar();
        $result = $this->validator->validate($calendar);

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->hasErrors());
        $this->assertContains('ICAL-COMP-001', $result->getErrorCodes());
    }

    public function testValidateValidCalendar(): void
    {
        // PRODID and VERSION are REQUIRED on VCALENDAR (RFC 5545 §3.6). This
        // fixture omitted both and still asserted a clean result: the two
        // calendar-level errors were raised and then discarded when the VEVENT
        // was validated, so the assertion only held because of that bug.
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN')->setVersion('2.0');
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $calendar->addComponent($event);

        $result = $this->validator->validate($calendar);

        $this->assertTrue($result->isEmpty());
    }

    public function testValidateVEventMissingDtstamp(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));

        $result = $this->validator->validateSingleComponent($event);

        $this->assertContains('ICAL-VEVENT-001', $result->getErrorCodes());
    }

    public function testValidateVEventMissingUid(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));

        $result = $this->validator->validateSingleComponent($event);

        $this->assertContains('ICAL-VEVENT-002', $result->getErrorCodes());
    }

    public function testValidateVEventInvalidStatus(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('STATUS', 'INVALID_STATUS'));

        $result = $this->validator->validateSingleComponent($event);

        $this->assertContains('ICAL-VEVENT-VAL-003', $result->getErrorCodes());
    }

    public function testValidateVEventValidStatuses(): void
    {
        $validStatuses = ['TENTATIVE', 'CONFIRMED', 'CANCELLED'];

        foreach ($validStatuses as $status) {
            $event = new VEvent();
            $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
            $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
            $event->addProperty($this->createProperty('STATUS', $status));

            $result = $this->validator->validateSingleComponent($event);

            $this->assertNotContains('ICAL-VEVENT-VAL-003', $result->getErrorCodes(), "Status {$status} should be valid");
        }
    }

    public function testValidateVEventDtEndAndDurationMutuallyExclusive(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('DTEND', '20240101T130000Z'));
        $event->addProperty($this->createProperty('DURATION', 'PT1H'));

        $result = $this->validator->validateSingleComponent($event);

        $this->assertContains('ICAL-VEVENT-VAL-001', $result->getErrorCodes());
    }

    public function testValidateVTodoMissingDtstamp(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));

        $result = $this->validator->validateSingleComponent($todo);

        $this->assertContains('ICAL-VTODO-001', $result->getErrorCodes());
    }

    public function testValidateVTodoMissingUid(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));

        $result = $this->validator->validateSingleComponent($todo);

        $this->assertContains('ICAL-VTODO-002', $result->getErrorCodes());
    }

    public function testValidateVTodoInvalidStatus(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $todo->addProperty($this->createProperty('STATUS', 'INVALID_STATUS'));

        $result = $this->validator->validateSingleComponent($todo);

        $this->assertContains('ICAL-VTODO-VAL-002', $result->getErrorCodes());
    }

    public function testValidateVTodoValidStatuses(): void
    {
        $validStatuses = ['NEEDS-ACTION', 'COMPLETED', 'IN-PROCESS', 'CANCELLED'];

        foreach ($validStatuses as $status) {
            $todo = new VTodo();
            $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
            $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));
            $todo->addProperty($this->createProperty('STATUS', $status));

            $result = $this->validator->validateSingleComponent($todo);

            $this->assertNotContains('ICAL-VTODO-VAL-002', $result->getErrorCodes(), "Status {$status} should be valid");
        }
    }

    public function testValidateVTodoDueAndDurationMutuallyExclusive(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $todo->addProperty($this->createProperty('DUE', '20240101T130000Z'));
        $todo->addProperty($this->createProperty('DURATION', 'PT1H'));

        $result = $this->validator->validateSingleComponent($todo);

        $this->assertContains('ICAL-VTODO-VAL-001', $result->getErrorCodes());
    }

    public function testValidateVJournalMissingDtstamp(): void
    {
        $journal = new VJournal();
        $journal->addProperty($this->createProperty('UID', 'test-uid@example.com'));

        $result = $this->validator->validateSingleComponent($journal);

        $this->assertContains('ICAL-VJOURNAL-001', $result->getErrorCodes());
    }

    public function testValidateVJournalInvalidStatus(): void
    {
        $journal = new VJournal();
        $journal->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $journal->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $journal->addProperty($this->createProperty('STATUS', 'INVALID_STATUS'));

        $result = $this->validator->validateSingleComponent($journal);

        $this->assertContains('ICAL-VJOURNAL-VAL-001', $result->getErrorCodes());
    }

    public function testValidateVTimezoneMissingTzid(): void
    {
        $timezone = new VTimezone();
        $observance = new Standard();
        $observance->addProperty($this->createProperty('TZOFFSETFROM', '+0000'));
        $observance->addProperty($this->createProperty('TZOFFSETTO', '+0000'));
        $observance->addProperty($this->createProperty('DTSTART', '19700301T020000Z'));
        $timezone->addComponent($observance);

        $result = $this->validator->validateSingleComponent($timezone);

        $this->assertContains('ICAL-TZ-001', $result->getErrorCodes());
    }

    public function testValidateVTimezoneObservanceMissingDtstart(): void
    {
        $timezone = new VTimezone();
        $timezone->addProperty($this->createProperty('TZID', 'Test/Timezone'));

        $observance = new Standard();
        $observance->addProperty($this->createProperty('TZOFFSETFROM', '+0000'));
        $observance->addProperty($this->createProperty('TZOFFSETTO', '+0000'));
        $timezone->addComponent($observance);

        $result = $this->validator->validateSingleComponent($timezone);

        $this->assertContains('ICAL-TZ-OBS-001', $result->getErrorCodes());
    }

    public function testValidateVAlarmMissingAction(): void
    {
        $alarm = new VAlarm();
        $alarm->addProperty($this->createProperty('TRIGGER', '-PT15M'));

        $result = $this->validator->validateSingleComponent($alarm);

        $this->assertContains('ICAL-ALARM-001', $result->getErrorCodes());
    }

    public function testValidateVAlarmMissingTrigger(): void
    {
        $alarm = new VAlarm();
        $alarm->addProperty($this->createProperty('ACTION', 'DISPLAY'));
        $alarm->addProperty($this->createProperty('DESCRIPTION', 'Reminder'));

        $result = $this->validator->validateSingleComponent($alarm);

        $this->assertContains('ICAL-ALARM-002', $result->getErrorCodes());
    }

    public function testValidateRRuleInvalidFormat(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('RRULE', 'FREQ=INVALID'));

        $result = $this->validator->validateSingleComponent($event);

        $this->assertContains('ICAL-RRULE-001', $result->getErrorCodes());
    }

    public function testValidateRRuleUntilAndCountMutuallyExclusive(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('RRULE', 'FREQ=DAILY;COUNT=10;UNTIL=20241231T235959Z'));

        $result = $this->validator->validateSingleComponent($event);

        $this->assertContains('ICAL-RRULE-003', $result->getErrorCodes());
    }

    public function testValidatePropertyMethod(): void
    {
        $property = new GenericProperty('SUMMARY', new TextValue('Test Event'));
        $result = $this->validator->validateProperty($property);

        $this->assertTrue($result->isEmpty());
    }

    public function testIsValidMethod(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN')->setVersion('2.0');
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $calendar->addComponent($event);

        $this->assertTrue($this->validator->isValid($calendar));
    }

    public function testIsValidMethodWithErrors(): void
    {
        $calendar = new VCalendar();

        $this->assertFalse($this->validator->isValid($calendar));
    }

    public function testValidateComponentMethodAlias(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));

        $errors = $this->validator->validateComponent($event);

        $this->assertEmpty($errors);
    }

    public function testValidateMultipleComponents(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN')->setVersion('2.0');

        $event1 = new VEvent();
        $event1->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event1->addProperty($this->createProperty('UID', 'event-1@example.com'));
        $calendar->addComponent($event1);

        $event2 = new VEvent();
        $event2->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event2->addProperty($this->createProperty('UID', 'event-2@example.com'));
        $calendar->addComponent($event2);

        $result = $this->validator->validate($calendar);

        $this->assertTrue($result->isEmpty());
    }

    public function testErrorCountsMethod(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN')->setVersion('2.0');
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $calendar->addComponent($event);

        $counts = $this->validator->getErrorCounts($calendar);

        $this->assertEquals(0, $counts['WARNING']);
        $this->assertEquals(0, $counts['ERROR']);
        $this->assertEquals(0, $counts['FATAL']);
    }

    // -------------------------------------------------------
    // VALUE parameter is case-insensitive per RFC 5545 §3.2.20
    // -------------------------------------------------------

    private function createPropertyWithParams(string $name, string $value, array $params): PropertyInterface
    {
        return new GenericProperty($name, new TextValue($value), $params);
    }

    public function testValidateDtStartDtEndBothDateLowercase(): void
    {
        $calendar = new VCalendar();
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test@example.com'));
        // Use lowercase "date" for VALUE parameter
        $event->addProperty($this->createPropertyWithParams('DTSTART', '20240101', ['VALUE' => 'date']));
        $event->addProperty($this->createPropertyWithParams('DTEND', '20240102', ['VALUE' => 'date']));
        $calendar->addComponent($event);

        $result = $this->validator->validate($calendar);

        // Should NOT get a DTSTART/DTEND type mismatch error
        $this->assertNotContains('ICAL-VEVENT-VAL-002', $result->getErrorCodes());
    }

    public function testValidateDtStartDtEndMixedCaseDate(): void
    {
        $calendar = new VCalendar();
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test@example.com'));
        // Mixed case "Date" on one, uppercase "DATE" on other
        $event->addProperty($this->createPropertyWithParams('DTSTART', '20240101', ['VALUE' => 'Date']));
        $event->addProperty($this->createPropertyWithParams('DTEND', '20240102', ['VALUE' => 'DATE']));
        $calendar->addComponent($event);

        $result = $this->validator->validate($calendar);

        // Both are DATE, just different cases - no mismatch
        $this->assertNotContains('ICAL-VEVENT-VAL-002', $result->getErrorCodes());
    }
}
