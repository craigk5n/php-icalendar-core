<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Component\VEvent;
use Icalendar\Property\GenericProperty;
use Icalendar\Recurrence\RecurrenceExpander;
use PHPUnit\Framework\TestCase;

/**
 * RFC 5545 Recurrence Rule Examples (Section 3.8.5.3)
 */
class Rfc5545RecurrenceTest extends TestCase
{
    private RecurrenceExpander $expander;

    protected function setUp(): void
    {
        $this->expander = new RecurrenceExpander();
    }

    /**
     * Daily for 10 occurrences:
     * DTSTART;TZID=America/New_York:19970902T090000
     * RRULE:FREQ=DAILY;COUNT=10
     */
    public function testDailyTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970902T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=DAILY;COUNT=10');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $this->assertEquals('1997-09-02', $occurrences[0]->getStart()->format('Y-m-d'));
        $this->assertEquals('1997-09-11', $occurrences[9]->getStart()->format('Y-m-d'));
    }

    /**
     * Daily until December 24, 1997:
     * DTSTART;TZID=America/New_York:19970902T090000
     * RRULE:FREQ=DAILY;UNTIL=19971224T000000Z
     */
    public function testDailyUntilDec24(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970902T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        // UNTIL is UTC
        $event->setRrule('FREQ=DAILY;UNTIL=19971224T000000Z');

        $occurrences = $this->expander->expandToArray($event);

        // Sept: 29 days (starting from 2nd), Oct: 31, Nov: 30, Dec: 23 (until 24th 00:00 UTC)
        // Dec 24 00:00 UTC is Dec 23 19:00 EST.
        // DTSTART is 09:00 EST.
        // Occurrences are at 09:00 EST.
        // Dec 23 09:00 EST is Dec 23 14:00 UTC, which is BEFORE Dec 24 00:00 UTC.
        // Dec 24 09:00 EST is Dec 24 14:00 UTC, which is AFTER Dec 24 00:00 UTC.
        // So last occurrence should be Dec 23.
        
        $last = end($occurrences);
        $this->assertEquals('1997-12-23', $last->getStart()->format('Y-m-d'));
        $this->assertEquals('1997-09-02', $occurrences[0]->getStart()->format('Y-m-d'));
    }

    /**
     * Every other week on Monday, Wednesday, and Friday starting on Monday, March 5, 2007:
     * DTSTART;TZID=US-Eastern:20070305T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;WKST=SU;BYDAY=MO,WE,FR
     * (RFC example text says "Every other week...". This implies infinite. We'll limit it.)
     */
    public function testEveryOtherWeekMonWedFri(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '20070305T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=WEEKLY;INTERVAL=2;WKST=SU;BYDAY=MO,WE,FR;COUNT=9');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(9, $occurrences);
        // Week 1: Mar 5 (Mon), Mar 7 (Wed), Mar 9 (Fri)
        $this->assertEquals('2007-03-05', $occurrences[0]->getStart()->format('Y-m-d'));
        $this->assertEquals('2007-03-07', $occurrences[1]->getStart()->format('Y-m-d'));
        $this->assertEquals('2007-03-09', $occurrences[2]->getStart()->format('Y-m-d'));
        
        // Week 3 (skip week 2): Mar 19 (Mon), Mar 21 (Wed), Mar 23 (Fri)
        $this->assertEquals('2007-03-19', $occurrences[3]->getStart()->format('Y-m-d'));
        $this->assertEquals('2007-03-21', $occurrences[4]->getStart()->format('Y-m-d'));
        $this->assertEquals('2007-03-23', $occurrences[5]->getStart()->format('Y-m-d'));
    }

    /**
     * Monthly on the first and last day of the month for 10 occurrences:
     * DTSTART;TZID=America/New_York:19970930T090000
     * RRULE:FREQ=MONTHLY;COUNT=10;BYMONTHDAY=1,-1
     */
    public function testMonthlyFirstAndLastDay(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970930T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;COUNT=10;BYMONTHDAY=1,-1');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        // Start: Sept 30
        $this->assertEquals('1997-09-30', $occurrences[0]->getStart()->format('Y-m-d'));
        // Oct: 1, 31
        $this->assertEquals('1997-10-01', $occurrences[1]->getStart()->format('Y-m-d'));
        $this->assertEquals('1997-10-31', $occurrences[2]->getStart()->format('Y-m-d'));
        // Nov: 1, 30
        $this->assertEquals('1997-11-01', $occurrences[3]->getStart()->format('Y-m-d'));
        $this->assertEquals('1997-11-30', $occurrences[4]->getStart()->format('Y-m-d'));
    }

    /**
     * Yearly on the 20th Monday of the year:
     * DTSTART;TZID=America/New_York:19970519T090000
     * RRULE:FREQ=YEARLY;BYDAY=20MO
     * 
     * RFC Expected: 1997-05-19, 1998-05-18, 1999-05-17...
     */
    public function testYearly20thMonday(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970519T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        // Limiting to 3 years for test
        $event->setRrule('FREQ=YEARLY;BYDAY=20MO;COUNT=3');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(3, $occurrences);
        $this->assertEquals('1997-05-19', $occurrences[0]->getStart()->format('Y-m-d'));
        $this->assertEquals('1998-05-18', $occurrences[1]->getStart()->format('Y-m-d'));
        $this->assertEquals('1999-05-17', $occurrences[2]->getStart()->format('Y-m-d'));
    }
}
