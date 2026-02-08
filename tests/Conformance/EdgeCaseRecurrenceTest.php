<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Component\VEvent;
use Icalendar\Property\GenericProperty;
use Icalendar\Recurrence\RecurrenceExpander;
use PHPUnit\Framework\TestCase;

/**
 * Tests for "Exotic" and complex recurrence rules to verify robustness
 * against competitor capabilities.
 */
class EdgeCaseRecurrenceTest extends TestCase
{
    private RecurrenceExpander $expander;

    #[\Override]
    protected function setUp(): void
    {
        $this->expander = new RecurrenceExpander();
    }

    /**
     * Test FREQ=YEARLY with BYMONTHDAY=-1
     * Should default to the month of DTSTART and find the last day of that month.
     */
    public function testYearlyByMonthDayNegativeOneDefaultMonth(): void
    {
        $event = new VEvent();
        // Start in January
        $event->addProperty(GenericProperty::create('DTSTART', '19970101T090000'));
        $event->setRrule('FREQ=YEARLY;COUNT=3;BYMONTHDAY=-1');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(3, $occurrences);
        // Should be Jan 31st of each year
        $this->assertEquals('1997-01-31', $occurrences[0]->getStart()->format('Y-m-d'));
        $this->assertEquals('1998-01-31', $occurrences[1]->getStart()->format('Y-m-d'));
        $this->assertEquals('1999-01-31', $occurrences[2]->getStart()->format('Y-m-d'));
    }

    /**
     * Test FREQ=YEARLY with BYMONTH=2 and BYMONTHDAY=-1 (Leap year check)
     */
    public function testYearlyFebruaryLastDayLeapYear(): void
    {
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('DTSTART', '19990101T090000'));
        // 2000 is a leap year
        $event->setRrule('FREQ=YEARLY;COUNT=4;BYMONTH=2;BYMONTHDAY=-1');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(4, $occurrences);
        $this->assertEquals('1999-02-28', $occurrences[0]->getStart()->format('Y-m-d'));
        $this->assertEquals('2000-02-29', $occurrences[1]->getStart()->format('Y-m-d')); // Leap!
        $this->assertEquals('2001-02-28', $occurrences[2]->getStart()->format('Y-m-d'));
        $this->assertEquals('2002-02-28', $occurrences[3]->getStart()->format('Y-m-d'));
    }

    /**
     * Test Complex BYSETPOS: Last weekday of the month
     * FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1
     */
    public function testMonthlyLastWeekday(): void
    {
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('DTSTART', '20240101T090000'));
        $event->setRrule('FREQ=MONTHLY;COUNT=3;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(3, $occurrences);
        // Jan 2024: 31st is Wed (Weekday) -> 2024-01-31
        $this->assertEquals('2024-01-31', $occurrences[0]->getStart()->format('Y-m-d'));
        
        // Feb 2024: 29th is Thu (Weekday) -> 2024-02-29
        $this->assertEquals('2024-02-29', $occurrences[1]->getStart()->format('Y-m-d'));
        
        // Mar 2024: 31st is Sun. Last weekday is Fri 29th.
        $this->assertEquals('2024-03-29', $occurrences[2]->getStart()->format('Y-m-d'));
    }

    /**
     * Test FREQ=YEARLY with BYYEARDAY=-1 (Last day of year)
     */
    public function testYearlyLastDayOfYear(): void
    {
        $event = new VEvent();
        $event->addProperty(GenericProperty::create('DTSTART', '19990101T090000'));
        $event->setRrule('FREQ=YEARLY;COUNT=3;BYYEARDAY=-1');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(3, $occurrences);
        $this->assertEquals('1999-12-31', $occurrences[0]->getStart()->format('Y-m-d'));
        $this->assertEquals('2000-12-31', $occurrences[1]->getStart()->format('Y-m-d'));
        $this->assertEquals('2001-12-31', $occurrences[2]->getStart()->format('Y-m-d'));
    }
}
