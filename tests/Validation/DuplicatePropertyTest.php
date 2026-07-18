<?php

declare(strict_types=1);

namespace Icalendar\Tests\Validation;

use Icalendar\Component\ComponentInterface;
use Icalendar\Component\Daylight;
use Icalendar\Component\Standard;
use Icalendar\Component\VAlarm;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTimezone;
use Icalendar\Component\VTodo;
use Icalendar\Property\GenericProperty;
use Icalendar\Validation\ErrorSeverity;
use Icalendar\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Properties RFC 5545 marks "MUST NOT occur more than once" must be reported
 * when they do.
 *
 * Nothing enforced this: a VEVENT with two UIDs, two DTSTAMPs and two SUMMARYs
 * validated clean, and the duplicates survived a parse/write round trip, so the
 * library happily reproduced a non-conformant calendar. Consumers reading "the"
 * UID get whichever copy getProperty() returns first, which makes the ambiguity
 * invisible rather than loud.
 *
 * Severity is ERROR, the RFC-correct reading, downgraded to WARNING in lenient
 * mode so importing a messy feed still yields data plus a diagnostic.
 */
class DuplicatePropertyTest extends TestCase
{
    private const CODE = 'ICAL-COMP-006';

    private function calendar(ComponentInterface ...$components): VCalendar
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN')->setVersion('2.0');
        foreach ($components as $component) {
            $calendar->addComponent($component);
        }

        return $calendar;
    }

    private function validEvent(): VEvent
    {
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('UID', 'e@example.com'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20260101T000000Z'));

        return $event;
    }

    /** The exact reproduction filed in the issue. */
    public function testIssueReproductionReportsEachDuplicatedProperty(): void
    {
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('UID', 'a@x'));
        $event->addProperty(GenericProperty::create('UID', 'b@x'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20260101T000000Z'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20260102T000000Z'));
        $event->addProperty(GenericProperty::create('DTSTART', '20260101T000000Z'));
        $event->addProperty(GenericProperty::create('SUMMARY', 'one'));
        $event->addProperty(GenericProperty::create('SUMMARY', 'two'));

        $errors = (new Validator())->validate($this->calendar($event))->getErrors();
        $duplicates = array_values(array_filter($errors, static fn ($e): bool => $e->code === self::CODE));

        self::assertCount(3, $duplicates, 'one error per duplicated property');

        $properties = array_map(static fn ($e): ?string => $e->property, $duplicates);
        sort($properties);
        self::assertSame(['DTSTAMP', 'SUMMARY', 'UID'], $properties);
    }

    public function testDuplicateIsAnErrorByDefault(): void
    {
        $event = $this->validEvent();
        $event->addProperty(GenericProperty::create('UID', 'second@x'));

        $errors = (new Validator())->validate($this->calendar($event))->getErrors();
        $duplicate = $this->firstDuplicate($errors);

        self::assertNotNull($duplicate);
        self::assertSame(ErrorSeverity::ERROR, $duplicate->severity);
    }

    public function testDuplicateIsAWarningInLenientMode(): void
    {
        $event = $this->validEvent();
        $event->addProperty(GenericProperty::create('UID', 'second@x'));

        $errors = (new Validator(Validator::LENIENT))->validate($this->calendar($event))->getErrors();
        $duplicate = $this->firstDuplicate($errors);

        self::assertNotNull($duplicate);
        self::assertSame(ErrorSeverity::WARNING, $duplicate->severity);
    }

    public function testCleanComponentReportsNothing(): void
    {
        $errors = (new Validator())->validate($this->calendar($this->validEvent()))->getErrors();

        self::assertSame([], array_filter($errors, static fn ($e): bool => $e->code === self::CODE));
    }

    /**
     * Properties RFC 5545 explicitly permits more than once must not be flagged.
     *
     * @return array<string, array{string}>
     */
    public static function repeatableProvider(): array
    {
        return [
            'ATTENDEE' => ['ATTENDEE'],
            'ATTACH' => ['ATTACH'],
            'CATEGORIES' => ['CATEGORIES'],
            'COMMENT' => ['COMMENT'],
            'CONTACT' => ['CONTACT'],
            'EXDATE' => ['EXDATE'],
            'RDATE' => ['RDATE'],
            'RELATED-TO' => ['RELATED-TO'],
            'RESOURCES' => ['RESOURCES'],
        ];
    }

    #[DataProvider('repeatableProvider')]
    public function testRepeatablePropertiesAreNotFlagged(string $property): void
    {
        $event = $this->validEvent();
        $event->addProperty(GenericProperty::create($property, 'one'));
        $event->addProperty(GenericProperty::create($property, 'two'));

        $errors = (new Validator())->validate($this->calendar($event))->getErrors();

        self::assertSame([], array_filter($errors, static fn ($e): bool => $e->code === self::CODE));
    }

    /**
     * VJOURNAL is the trap: DESCRIPTION MAY occur more than once there
     * (RFC 5545 §3.6.3) while it must not on VEVENT/VTODO.
     */
    public function testVJournalAllowsRepeatedDescription(): void
    {
        $journal = new VJournal();
        $journal->addProperty(GenericProperty::create('UID', 'j@x'));
        $journal->addProperty(GenericProperty::create('DTSTAMP', '20260101T000000Z'));
        $journal->addProperty(GenericProperty::create('DESCRIPTION', 'one'));
        $journal->addProperty(GenericProperty::create('DESCRIPTION', 'two'));

        $errors = (new Validator())->validate($this->calendar($journal))->getErrors();

        self::assertSame([], array_filter($errors, static fn ($e): bool => $e->code === self::CODE));
    }

    public function testVEventRejectsRepeatedDescription(): void
    {
        $event = $this->validEvent();
        $event->addProperty(GenericProperty::create('DESCRIPTION', 'one'));
        $event->addProperty(GenericProperty::create('DESCRIPTION', 'two'));

        $errors = (new Validator())->validate($this->calendar($event))->getErrors();

        self::assertNotNull($this->firstDuplicate($errors));
    }

    /** @return array<string, array{callable(): ComponentInterface, string}> */
    public static function componentProvider(): array
    {
        return [
            'VEVENT' => [static function (): ComponentInterface {
                $c = new VEvent();
                $c->addProperty(GenericProperty::create('UID', 'a@x'));
                $c->addProperty(GenericProperty::create('DTSTAMP', '20260101T000000Z'));
                return $c;
            }, 'SUMMARY'],
            'VTODO' => [static function (): ComponentInterface {
                $c = new VTodo();
                $c->addProperty(GenericProperty::create('UID', 'a@x'));
                $c->addProperty(GenericProperty::create('DTSTAMP', '20260101T000000Z'));
                return $c;
            }, 'SUMMARY'],
            'VJOURNAL' => [static function (): ComponentInterface {
                $c = new VJournal();
                $c->addProperty(GenericProperty::create('UID', 'a@x'));
                $c->addProperty(GenericProperty::create('DTSTAMP', '20260101T000000Z'));
                return $c;
            }, 'SUMMARY'],
            'VFREEBUSY' => [static function (): ComponentInterface {
                $c = new VFreeBusy();
                $c->addProperty(GenericProperty::create('UID', 'a@x'));
                $c->addProperty(GenericProperty::create('DTSTAMP', '20260101T000000Z'));
                return $c;
            }, 'ORGANIZER'],
        ];
    }

    /**
     * @param callable(): ComponentInterface $factory
     */
    #[DataProvider('componentProvider')]
    public function testDuplicatesAreDetectedOnEveryComponentType(callable $factory, string $property): void
    {
        $component = $factory();
        $component->addProperty(GenericProperty::create($property, 'one'));
        $component->addProperty(GenericProperty::create($property, 'two'));

        $errors = (new Validator())->validate($this->calendar($component))->getErrors();

        self::assertNotNull($this->firstDuplicate($errors), "{$property} duplicate must be reported");
    }

    public function testDuplicatesAreDetectedInsideVAlarm(): void
    {
        $alarm = new VAlarm();
        $alarm->addProperty(GenericProperty::create('ACTION', 'DISPLAY'));
        $alarm->addProperty(GenericProperty::create('ACTION', 'AUDIO'));
        $alarm->addProperty(GenericProperty::create('TRIGGER', '-PT15M'));
        $alarm->addProperty(GenericProperty::create('DESCRIPTION', 'd'));

        $event = $this->validEvent();
        $event->addComponent($alarm);

        $errors = (new Validator())->validate($this->calendar($event))->getErrors();

        self::assertNotNull($this->firstDuplicate($errors), 'sub-component duplicates must be reported');
    }

    public function testDuplicatesAreDetectedInsideTimezoneObservances(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzId('America/New_York');

        $standard = new Standard();
        $standard->setDtStart(new \DateTime('2026-11-01T02:00:00', new \DateTimeZone('UTC')));
        $standard->setTzOffsetTo(-18000);
        $standard->setTzOffsetFrom(-14400);
        // A second TZOFFSETTO: MUST NOT occur more than once.
        $standard->addProperty(GenericProperty::create('TZOFFSETTO', '-0600'));
        $timezone->addStandard($standard);

        $daylight = new Daylight();
        $daylight->setDtStart(new \DateTime('2026-03-08T02:00:00', new \DateTimeZone('UTC')));
        $daylight->setTzOffsetTo(-14400);
        $daylight->setTzOffsetFrom(-18000);
        $timezone->addDaylight($daylight);

        $errors = (new Validator())->validate($this->calendar($timezone))->getErrors();

        self::assertNotNull($this->firstDuplicate($errors));
    }

    public function testDuplicateCalendarLevelPropertyIsReported(): void
    {
        $calendar = $this->calendar($this->validEvent());
        $calendar->addProperty(GenericProperty::create('PRODID', '-//Second//Second//EN'));

        $errors = (new Validator())->validate($calendar)->getErrors();
        $duplicate = $this->firstDuplicate($errors);

        self::assertNotNull($duplicate);
        self::assertSame('PRODID', $duplicate->property);
    }

    /**
     * @param array<int, \Icalendar\Validation\ValidationError> $errors
     */
    private function firstDuplicate(array $errors): ?\Icalendar\Validation\ValidationError
    {
        foreach ($errors as $error) {
            if ($error->code === self::CODE) {
                return $error;
            }
        }

        return null;
    }
}
