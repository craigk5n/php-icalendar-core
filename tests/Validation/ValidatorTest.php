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
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

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
        $errors = $this->validator->validate($calendar);

        $this->assertNotEmpty($errors);
        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-COMP-001', $errorCodes);
    }

    public function testValidateValidCalendar(): void
    {
        $calendar = new VCalendar();
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $calendar->addComponent($event);

        $errors = $this->validator->validate($calendar);

        $this->assertEmpty($errors);
    }

    public function testValidateVEventMissingDtstamp(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));

        $errors = $this->validator->validateSingleComponent($event);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VEVENT-001', $errorCodes);
    }

    public function testValidateVEventMissingUid(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));

        $errors = $this->validator->validateSingleComponent($event);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VEVENT-002', $errorCodes);
    }

    public function testValidateVEventInvalidStatus(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('STATUS', 'INVALID_STATUS'));

        $errors = $this->validator->validateSingleComponent($event);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VEVENT-VAL-003', $errorCodes);
    }

    public function testValidateVEventValidStatuses(): void
    {
        $validStatuses = ['TENTATIVE', 'CONFIRMED', 'CANCELLED'];

        foreach ($validStatuses as $status) {
            $event = new VEvent();
            $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
            $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
            $event->addProperty($this->createProperty('STATUS', $status));

            $errors = $this->validator->validateSingleComponent($event);

            $errorCodes = array_map(fn($e) => $e->code, $errors);
            $this->assertNotContains('ICAL-VEVENT-VAL-003', $errorCodes, "Status {$status} should be valid");
        }
    }

    public function testValidateVEventDtEndAndDurationMutuallyExclusive(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('DTEND', '20240101T130000Z'));
        $event->addProperty($this->createProperty('DURATION', 'PT1H'));

        $errors = $this->validator->validateSingleComponent($event);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VEVENT-VAL-001', $errorCodes);
    }

    public function testValidateVTodoMissingDtstamp(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));

        $errors = $this->validator->validateSingleComponent($todo);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VTODO-001', $errorCodes);
    }

    public function testValidateVTodoMissingUid(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));

        $errors = $this->validator->validateSingleComponent($todo);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VTODO-002', $errorCodes);
    }

    public function testValidateVTodoInvalidStatus(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $todo->addProperty($this->createProperty('STATUS', 'INVALID_STATUS'));

        $errors = $this->validator->validateSingleComponent($todo);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VTODO-VAL-002', $errorCodes);
    }

    public function testValidateVTodoValidStatuses(): void
    {
        $validStatuses = ['NEEDS-ACTION', 'COMPLETED', 'IN-PROCESS', 'CANCELLED'];

        foreach ($validStatuses as $status) {
            $todo = new VTodo();
            $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
            $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));
            $todo->addProperty($this->createProperty('STATUS', $status));

            $errors = $this->validator->validateSingleComponent($todo);

            $errorCodes = array_map(fn($e) => $e->code, $errors);
            $this->assertNotContains('ICAL-VTODO-VAL-002', $errorCodes, "Status {$status} should be valid");
        }
    }

    public function testValidateVTodoDueAndDurationMutuallyExclusive(): void
    {
        $todo = new VTodo();
        $todo->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $todo->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $todo->addProperty($this->createProperty('DUE', '20240101T130000Z'));
        $todo->addProperty($this->createProperty('DURATION', 'PT1H'));

        $errors = $this->validator->validateSingleComponent($todo);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VTODO-VAL-001', $errorCodes);
    }

    public function testValidateVJournalMissingDtstamp(): void
    {
        $journal = new VJournal();
        $journal->addProperty($this->createProperty('UID', 'test-uid@example.com'));

        $errors = $this->validator->validateSingleComponent($journal);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VJOURNAL-001', $errorCodes);
    }

    public function testValidateVJournalInvalidStatus(): void
    {
        $journal = new VJournal();
        $journal->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $journal->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $journal->addProperty($this->createProperty('STATUS', 'INVALID_STATUS'));

        $errors = $this->validator->validateSingleComponent($journal);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-VJOURNAL-VAL-001', $errorCodes);
    }

    public function testValidateVTimezoneMissingTzid(): void
    {
        $timezone = new VTimezone();
        $observance = new Standard();
        $observance->addProperty($this->createProperty('TZOFFSETFROM', '+0000'));
        $observance->addProperty($this->createProperty('TZOFFSETTO', '+0000'));
        $observance->addProperty($this->createProperty('DTSTART', '19700301T020000Z'));
        $timezone->addComponent($observance);

        $errors = $this->validator->validateSingleComponent($timezone);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-TZ-001', $errorCodes);
    }

    public function testValidateVTimezoneObservanceMissingDtstart(): void
    {
        $timezone = new VTimezone();
        $timezone->addProperty($this->createProperty('TZID', 'Test/Timezone'));

        $observance = new Standard();
        $observance->addProperty($this->createProperty('TZOFFSETFROM', '+0000'));
        $observance->addProperty($this->createProperty('TZOFFSETTO', '+0000'));
        $timezone->addComponent($observance);

        $errors = $this->validator->validateSingleComponent($timezone);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-TZ-OBS-001', $errorCodes);
    }

    public function testValidateVAlarmMissingAction(): void
    {
        $alarm = new VAlarm();
        $alarm->addProperty($this->createProperty('TRIGGER', '-PT15M'));

        $errors = $this->validator->validateSingleComponent($alarm);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-ALARM-001', $errorCodes);
    }

    public function testValidateVAlarmMissingTrigger(): void
    {
        $alarm = new VAlarm();
        $alarm->addProperty($this->createProperty('ACTION', 'DISPLAY'));
        $alarm->addProperty($this->createProperty('DESCRIPTION', 'Reminder'));

        $errors = $this->validator->validateSingleComponent($alarm);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-ALARM-002', $errorCodes);
    }

    public function testValidateRRuleInvalidFormat(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('RRULE', 'FREQ=INVALID'));

        $errors = $this->validator->validateSingleComponent($event);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-RRULE-001', $errorCodes);
    }

    public function testValidateRRuleUntilAndCountMutuallyExclusive(): void
    {
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $event->addProperty($this->createProperty('RRULE', 'FREQ=DAILY;COUNT=10;UNTIL=20241231T235959Z'));

        $errors = $this->validator->validateSingleComponent($event);

        $errorCodes = array_map(fn($e) => $e->code, $errors);
        $this->assertContains('ICAL-RRULE-003', $errorCodes);
    }

    public function testValidatePropertyMethod(): void
    {
        $property = new GenericProperty('SUMMARY', new TextValue('Test Event'));
        $errors = $this->validator->validateProperty($property);

        $this->assertEmpty($errors);
    }

    public function testIsValidMethod(): void
    {
        $calendar = new VCalendar();
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

        $event1 = new VEvent();
        $event1->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event1->addProperty($this->createProperty('UID', 'event-1@example.com'));
        $calendar->addComponent($event1);

        $event2 = new VEvent();
        $event2->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event2->addProperty($this->createProperty('UID', 'event-2@example.com'));
        $calendar->addComponent($event2);

        $errors = $this->validator->validate($calendar);

        $this->assertEmpty($errors);
    }

    public function testErrorCountsMethod(): void
    {
        $calendar = new VCalendar();
        $event = new VEvent();
        $event->addProperty($this->createProperty('DTSTAMP', '20240101T120000Z'));
        $event->addProperty($this->createProperty('UID', 'test-uid@example.com'));
        $calendar->addComponent($event);

        $counts = $this->validator->getErrorCounts($calendar);

        $this->assertEquals(0, $counts['WARNING']);
        $this->assertEquals(0, $counts['ERROR']);
        $this->assertEquals(0, $counts['FATAL']);
    }
}
