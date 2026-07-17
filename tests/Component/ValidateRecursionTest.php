<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\Available;
use Icalendar\Component\Daylight;
use Icalendar\Component\GenericComponent;
use Icalendar\Component\VAlarm;
use Icalendar\Component\VAvailability;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VTimezone;
use Icalendar\Exception\ValidationException;
use Icalendar\Property\GenericProperty;
use PHPUnit\Framework\TestCase;

/**
 * Component validate() recurses into sub-components.
 *
 * VCalendar::validate() checked PRODID and VERSION and returned, so
 * VEvent::validate() and friends were unreachable through the obvious entry
 * point: a calendar containing a VEVENT with no UID and no DTSTAMP -- both
 * required by RFC 5545 -- passed $calendar->validate() without complaint.
 *
 * The recursion now lives once in AbstractComponent::validate(), which runs a
 * component's own checks (validateSelf()) and then descends into every child.
 * It stays fail-fast: the first violation, in document order, is thrown.
 *
 * For collecting *all* errors rather than stopping at the first, Validator is
 * still the tool; this only makes the intuitive call do the intuitive thing.
 */
class ValidateRecursionTest extends TestCase
{
    private function validCalendar(): VCalendar
    {
        $calendar = new VCalendar();
        $calendar->addProperty(GenericProperty::create('PRODID', '-//test//test//EN'));
        $calendar->addProperty(GenericProperty::create('VERSION', '2.0'));

        return $calendar;
    }

    private function validEvent(): VEvent
    {
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('UID', 'test-uid'));
        $event->addProperty(GenericProperty::create('DTSTAMP', '20240101T000000Z'));

        return $event;
    }

    /** The headline: an invalid child must fail the parent's validate(). */
    public function testInvalidChildFailsCalendarValidation(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent(new VEvent()); // no UID, no DTSTAMP

        $this->expectException(ValidationException::class);
        $calendar->validate();
    }

    public function testErrorFromChildIdentifiesTheChild(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent(new VEvent());

        try {
            $calendar->validate();
            $this->fail('expected the invalid VEVENT to fail validation');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('VEVENT', $e->getMessage());
        }
    }

    public function testValidTreePasses(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent($this->validEvent());

        $this->assertNull($calendar->validate());
    }

    /** Recursion is not one level deep: VCALENDAR -> VEVENT -> bad VALARM. */
    public function testInvalidGrandchildFailsValidation(): void
    {
        $event = $this->validEvent();
        $event->addComponent(new VAlarm()); // no ACTION, no TRIGGER

        $calendar = $this->validCalendar();
        $calendar->addComponent($event);

        $this->expectException(ValidationException::class);
        $calendar->validate();
    }

    /** A component's own checks run before its children are descended. */
    public function testSelfIsValidatedBeforeChildren(): void
    {
        $calendar = new VCalendar(); // missing PRODID and VERSION
        $calendar->addComponent(new VEvent()); // also invalid

        try {
            $calendar->validate();
            $this->fail('expected validation to fail');
        } catch (ValidationException $e) {
            // The calendar's own defect must surface before the child's.
            $this->assertStringContainsString('PRODID', $e->getMessage());
        }
    }

    /** Extension children carry no rules and must not break the descent. */
    public function testGenericChildDoesNotBreakRecursion(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent(new GenericComponent('X-CUSTOM'));
        $calendar->addComponent($this->validEvent());

        $this->assertNull($calendar->validate());
    }

    /** A valid child sitting after an invalid one still lets the invalid one throw. */
    public function testInvalidChildAmongValidOnesStillThrows(): void
    {
        $calendar = $this->validCalendar();
        $calendar->addComponent($this->validEvent());
        $calendar->addComponent(new VEvent()); // invalid, second
        $calendar->addComponent($this->validEvent());

        $this->expectException(ValidationException::class);
        $calendar->validate();
    }

    // -- regressions on the two components that already recursed manually --

    public function testVTimezoneStillValidatesObservances(): void
    {
        $timezone = new VTimezone();
        $timezone->addProperty(GenericProperty::create('TZID', 'America/New_York'));
        $timezone->addComponent(new Daylight()); // observance missing its required properties

        $this->expectException(ValidationException::class);
        $timezone->validate();
    }

    public function testVTimezoneOwnRulesStillApply(): void
    {
        // TZID present but no STANDARD/DAYLIGHT observance.
        $timezone = new VTimezone();
        $timezone->addProperty(GenericProperty::create('TZID', 'America/New_York'));

        $this->expectException(ValidationException::class);
        $timezone->validate();
    }

    public function testVAvailabilityStillValidatesChildren(): void
    {
        $availability = new VAvailability();
        $availability->addProperty(GenericProperty::create('DTSTAMP', '20240101T000000Z'));
        $availability->addProperty(GenericProperty::create('UID', 'test-uid'));
        $availability->addComponent(new Available()); // missing required properties

        $this->expectException(ValidationException::class);
        $availability->validate();
    }

    /**
     * Recursion must not double-process children. A spy component counts how
     * many times its own validation runs when reached through a parent.
     */
    public function testChildIsValidatedExactlyOnce(): void
    {
        $spy = new class extends VEvent {
            public int $selfValidations = 0;

            #[\Override]
            protected function validateSelf(): void
            {
                $this->selfValidations++;
                parent::validateSelf();
            }
        };
        $spy->addProperty(GenericProperty::create('UID', 'spy-uid'));
        $spy->addProperty(GenericProperty::create('DTSTAMP', '20240101T000000Z'));

        $calendar = $this->validCalendar();
        $calendar->addComponent($spy);
        $calendar->validate();

        $this->assertSame(1, $spy->selfValidations);
    }
}
