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

    #[\Override]
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

    // ========================================================================
    // INTERVAL Tests
    // ========================================================================

    /**
     * RFC ex 4: Every 10 days, 5 occurrences
     * DTSTART;TZID=America/New_York:19970902T090000
     * RRULE:FREQ=DAILY;INTERVAL=10;COUNT=5
     *
     * Expected: Sep 2, Sep 12, Sep 22, Oct 2, Oct 12
     */
    public function testEveryTenDaysFiveOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970902T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=DAILY;INTERVAL=10;COUNT=5');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(5, $occurrences);
        $expected = ['1997-09-02', '1997-09-12', '1997-09-22', '1997-10-02', '1997-10-12'];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYDAY in WEEKLY Tests
    // ========================================================================

    /**
     * RFC ex 9: Weekly on Tuesday and Thursday for 5 weeks (10 occurrences)
     * DTSTART;TZID=America/New_York:19970902T090000
     * RRULE:FREQ=WEEKLY;COUNT=10;WKST=SU;BYDAY=TU,TH
     *
     * Expected: Sep 2,4,9,11,16,18,23,25,30; Oct 2
     */
    public function testWeeklyTuesdayThursdayTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970902T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=WEEKLY;COUNT=10;WKST=SU;BYDAY=TU,TH');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-09-02', '1997-09-04', '1997-09-09', '1997-09-11', '1997-09-16',
            '1997-09-18', '1997-09-23', '1997-09-25', '1997-09-30', '1997-10-02',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYDAY ordinal in MONTHLY Tests
    // ========================================================================

    /**
     * RFC ex 12: Monthly on the 1st Friday for 10 occurrences
     * DTSTART;TZID=America/New_York:19970905T090000
     * RRULE:FREQ=MONTHLY;COUNT=10;BYDAY=1FR
     *
     * Expected: Sep 5; Oct 3; Nov 7; Dec 5; Jan 2; Feb 6; Mar 6; Apr 3; May 1; Jun 5
     */
    public function testMonthlyFirstFridayTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970905T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;COUNT=10;BYDAY=1FR');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-09-05', '1997-10-03', '1997-11-07', '1997-12-05', '1998-01-02',
            '1998-02-06', '1998-03-06', '1998-04-03', '1998-05-01', '1998-06-05',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    /**
     * RFC ex 14: Every other month on the 1st and last Sunday of the month for 10 occurrences
     * DTSTART;TZID=America/New_York:19970907T090000
     * RRULE:FREQ=MONTHLY;INTERVAL=2;COUNT=10;BYDAY=1SU,-1SU
     *
     * Expected: Sep 7,28; Nov 2,30; Jan 4,25; Mar 1,29; May 3,31
     */
    public function testEveryOtherMonthFirstAndLastSundayTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970907T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;INTERVAL=2;COUNT=10;BYDAY=1SU,-1SU');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-09-07', '1997-09-28', '1997-11-02', '1997-11-30', '1998-01-04',
            '1998-01-25', '1998-03-01', '1998-03-29', '1998-05-03', '1998-05-31',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    /**
     * RFC ex 15: Monthly on the second-to-last Monday of the month for 6 occurrences
     * DTSTART;TZID=America/New_York:19970922T090000
     * RRULE:FREQ=MONTHLY;COUNT=6;BYDAY=-2MO
     *
     * Expected: Sep 22; Oct 20; Nov 17; Dec 22; Jan 19; Feb 16
     */
    public function testMonthlySecondToLastMondaySixOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970922T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;COUNT=6;BYDAY=-2MO');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(6, $occurrences);
        $expected = [
            '1997-09-22', '1997-10-20', '1997-11-17', '1997-12-22', '1998-01-19', '1998-02-16',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYMONTHDAY (negative) Tests
    // ========================================================================

    /**
     * RFC ex 16: Monthly on the third-to-the-last day of the month, 6 occurrences
     * DTSTART;TZID=America/New_York:19970928T090000
     * RRULE:FREQ=MONTHLY;BYMONTHDAY=-3;COUNT=6
     *
     * Expected: Sep 28; Oct 29; Nov 28; Dec 29; Jan 29; Feb 26
     */
    public function testMonthlyNegativeThirdDaySixOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970928T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;BYMONTHDAY=-3;COUNT=6');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(6, $occurrences);
        $expected = [
            '1997-09-28', '1997-10-29', '1997-11-28', '1997-12-29', '1998-01-29', '1998-02-26',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // Multiple BYMONTHDAY Tests
    // ========================================================================

    /**
     * RFC ex 19: Every 18 months on the 10th thru 15th of the month for 10 occurrences
     * DTSTART;TZID=America/New_York:19970910T090000
     * RRULE:FREQ=MONTHLY;INTERVAL=18;COUNT=10;BYMONTHDAY=10,11,12,13,14,15
     *
     * Expected: Sep 10-15 (1997); Mar 10-13 (1999)
     */
    public function testEvery18MonthsDays10To15TenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970910T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;INTERVAL=18;COUNT=10;BYMONTHDAY=10,11,12,13,14,15');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-09-10', '1997-09-11', '1997-09-12', '1997-09-13', '1997-09-14', '1997-09-15',
            '1999-03-10', '1999-03-11', '1999-03-12', '1999-03-13',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYMONTH with YEARLY Tests
    // ========================================================================

    /**
     * RFC ex 21: Yearly in June and July for 10 occurrences
     * DTSTART;TZID=America/New_York:19970610T090000
     * RRULE:FREQ=YEARLY;COUNT=10;BYMONTH=6,7
     *
     * Expected: Jun 10, Jul 10 for 5 years (1997-2001)
     */
    public function testYearlyJuneJulyTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970610T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=YEARLY;COUNT=10;BYMONTH=6,7');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-06-10', '1997-07-10',
            '1998-06-10', '1998-07-10',
            '1999-06-10', '1999-07-10',
            '2000-06-10', '2000-07-10',
            '2001-06-10', '2001-07-10',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    /**
     * RFC ex 22: Every other year on January, February, and March for 10 occurrences
     * DTSTART;TZID=America/New_York:19970310T090000
     * RRULE:FREQ=YEARLY;INTERVAL=2;COUNT=10;BYMONTH=1,2,3
     *
     * Expected: Mar 10 (1997); Jan 10, Feb 10, Mar 10 (1999); same (2001); Jan 10 (2003)
     */
    public function testEveryOtherYearJanFebMarTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970310T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=YEARLY;INTERVAL=2;COUNT=10;BYMONTH=1,2,3');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-03-10',
            '1999-01-10', '1999-02-10', '1999-03-10',
            '2001-01-10', '2001-02-10', '2001-03-10',
            '2003-01-10', '2003-02-10', '2003-03-10',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYYEARDAY Tests
    // ========================================================================

    /**
     * RFC ex 23: Every 3rd year on the 1st, 100th, and 200th day for 10 occurrences
     * DTSTART;TZID=America/New_York:19970101T090000
     * RRULE:FREQ=YEARLY;INTERVAL=3;COUNT=10;BYYEARDAY=1,100,200
     *
     * Expected: Jan 1, Apr 10, Jul 19 (1997); Jan 1, Apr 9, Jul 18 (2000);
     *           Jan 1, Apr 10, Jul 19 (2003); Jan 1 (2006)
     */
    public function testEveryThirdYearByYearDayTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970101T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=YEARLY;INTERVAL=3;COUNT=10;BYYEARDAY=1,100,200');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-01-01', '1997-04-10', '1997-07-19',
            '2000-01-01', '2000-04-09', '2000-07-18',
            '2003-01-01', '2003-04-10', '2003-07-19',
            '2006-01-01',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYWEEKNO Tests
    // ========================================================================

    /**
     * RFC ex 25: Monday of week number 20 (where the default start of the week is Monday), 3 occurrences
     * DTSTART;TZID=America/New_York:19970512T090000
     * RRULE:FREQ=YEARLY;BYWEEKNO=20;BYDAY=MO;COUNT=3
     *
     * Expected: May 12 (1997); May 11 (1998); May 17 (1999)
     */
    public function testYearlyByWeekNoMondayThreeOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970512T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=YEARLY;BYWEEKNO=20;BYDAY=MO;COUNT=3');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(3, $occurrences);
        $expected = ['1997-05-12', '1998-05-11', '1999-05-17'];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYDAY + BYMONTH in YEARLY Tests
    // ========================================================================

    /**
     * RFC ex 26: Every Thursday in March, forever (limited to 11 occurrences)
     * DTSTART;TZID=America/New_York:19970313T090000
     * RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=TH;COUNT=11
     *
     * Expected: Mar 13,20,27 (1997); Mar 5,12,19,26 (1998); Mar 4,11,18,25 (1999)
     */
    public function testYearlyEveryThursdayInMarchElevenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970313T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=YEARLY;BYMONTH=3;BYDAY=TH;COUNT=11');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(11, $occurrences);
        $expected = [
            '1997-03-13', '1997-03-20', '1997-03-27',
            '1998-03-05', '1998-03-12', '1998-03-19', '1998-03-26',
            '1999-03-04', '1999-03-11', '1999-03-18', '1999-03-25',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYDAY + BYMONTHDAY combined (Friday the 13th)
    // ========================================================================

    /**
     * RFC ex 28: Every Friday the 13th, 5 occurrences
     * DTSTART;TZID=America/New_York:19970902T090000
     * RRULE:FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13;COUNT=5
     *
     * Note: DTSTART (Sep 2) is NOT a Friday the 13th so it is NOT included in output.
     * (RFC 5545: DTSTART is only included if it matches the RRULE pattern)
     *
     * Expected: Feb 13, Mar 13, Nov 13 (1998); Aug 13 (1999); Oct 13 (2000)
     */
    public function testFridayThe13thFiveOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970902T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13;COUNT=5');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(5, $occurrences);
        $expected = [
            '1998-02-13', '1998-03-13', '1998-11-13', '1999-08-13', '2000-10-13',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // First Saturday after first Sunday of the month
    // ========================================================================

    /**
     * RFC ex 29: The first Saturday that follows the first Sunday of the month, 10 occurrences
     * DTSTART;TZID=America/New_York:19970913T090000
     * RRULE:FREQ=MONTHLY;BYDAY=SA;BYMONTHDAY=7,8,9,10,11,12,13;COUNT=10
     *
     * Expected: Sep 13; Oct 11; Nov 8; Dec 13; Jan 10; Feb 7; Mar 7; Apr 11; May 9; Jun 13
     */
    public function testFirstSaturdayAfterFirstSundayTenOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970913T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;BYDAY=SA;BYMONTHDAY=7,8,9,10,11,12,13;COUNT=10');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(10, $occurrences);
        $expected = [
            '1997-09-13', '1997-10-11', '1997-11-08', '1997-12-13', '1998-01-10',
            '1998-02-07', '1998-03-07', '1998-04-11', '1998-05-09', '1998-06-13',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // U.S. Presidential Election Day
    // ========================================================================

    /**
     * RFC ex 30: Every 4 years, the first Tuesday after a Monday in November (Election Day)
     * DTSTART;TZID=America/New_York:19961105T090000
     * RRULE:FREQ=YEARLY;INTERVAL=4;BYMONTH=11;BYDAY=TU;BYMONTHDAY=2,3,4,5,6,7,8;COUNT=4
     *
     * Expected: Nov 5 (1996); Nov 7 (2000); Nov 2 (2004); Nov 4 (2008)
     */
    public function testUSPresidentialElectionDayFourOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19961105T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=YEARLY;INTERVAL=4;BYMONTH=11;BYDAY=TU;BYMONTHDAY=2,3,4,5,6,7,8;COUNT=4');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(4, $occurrences);
        $expected = ['1996-11-05', '2000-11-07', '2004-11-02', '2008-11-04'];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYSETPOS Tests
    // ========================================================================

    /**
     * RFC ex 31: The third instance of either a Tuesday, Wednesday, or Thursday, monthly, 3 occurrences
     * DTSTART;TZID=America/New_York:19970904T090000
     * RRULE:FREQ=MONTHLY;COUNT=3;BYDAY=TU,WE,TH;BYSETPOS=3
     *
     * Expected: Sep 4; Oct 7; Nov 6
     */
    public function testMonthlyThirdInstanceTuWeThThreeOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970904T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;COUNT=3;BYDAY=TU,WE,TH;BYSETPOS=3');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(3, $occurrences);
        $expected = ['1997-09-04', '1997-10-07', '1997-11-06'];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    /**
     * RFC ex 32: The second-to-last weekday of the month, 6 occurrences
     * DTSTART;TZID=America/New_York:19970929T090000
     * RRULE:FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-2;COUNT=6
     *
     * Expected: Sep 29; Oct 30; Nov 27; Dec 30; Jan 29; Feb 26
     */
    public function testMonthlySecondToLastWeekdaySixOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970929T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-2;COUNT=6');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(6, $occurrences);
        $expected = [
            '1997-09-29', '1997-10-30', '1997-11-27', '1997-12-30', '1998-01-29', '1998-02-26',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // WKST difference Tests
    // ========================================================================

    /**
     * RFC ex 37: WKST=MO - An example where the days generated make a difference because of WKST
     * DTSTART;TZID=America/New_York:19970805T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=MO
     *
     * With MO as WKST, weeks are Mon-Sun: TU Aug 5 and SU Aug 10 are in same week.
     * Expected: Aug 5, Aug 10, Aug 19, Aug 24
     */
    public function testWeeklyInterval2WkstMondayFourOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970805T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=MO');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(4, $occurrences);
        $expected = ['1997-08-05', '1997-08-10', '1997-08-19', '1997-08-24'];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    /**
     * RFC ex 38: WKST=SU - Same RRULE as above but with WKST=SU changes the result
     * DTSTART;TZID=America/New_York:19970805T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=SU
     *
     * With SU as WKST, weeks are Sun-Sat: TU Aug 5 and SU Aug 10 are in DIFFERENT weeks.
     * Expected: Aug 5, Aug 17, Aug 19, Aug 31
     */
    public function testWeeklyInterval2WkstSundayFourOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19970805T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=SU');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(4, $occurrences);
        $expected = ['1997-08-05', '1997-08-17', '1997-08-19', '1997-08-31'];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // Invalid date handling Tests
    // ========================================================================

    /**
     * RFC ex 39: Feb 30 skipped - Monthly on the 15th and 30th with COUNT=5
     * DTSTART;VALUE=DATE:20070115
     * RRULE:FREQ=MONTHLY;BYMONTHDAY=15,30;COUNT=5
     *
     * February has no 30th, so it is skipped.
     * Expected: Jan 15, Jan 30, Feb 15, Mar 15, Mar 30
     */
    public function testMonthlyByMonthDayFeb30SkippedFiveOccurrences(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '20070115');
        $dtstart->setParameter('VALUE', 'DATE');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=MONTHLY;BYMONTHDAY=15,30;COUNT=5');

        $occurrences = $this->expander->expandToArray($event);

        $this->assertCount(5, $occurrences);
        $expected = [
            '2007-01-15', '2007-01-30', '2007-02-15', '2007-03-15', '2007-03-30',
        ];
        foreach ($expected as $i => $date) {
            $this->assertEquals($date, $occurrences[$i]->getStart()->format('Y-m-d'),
                "Occurrence $i should be $date");
        }
    }

    // ========================================================================
    // BYMONTH with DAILY Tests
    // ========================================================================

    /**
     * RFC ex 5: Every day in January, for 3 years
     * DTSTART;TZID=America/New_York:19980101T090000
     * RRULE:FREQ=DAILY;UNTIL=20000131T140000Z;BYMONTH=1
     *
     * Expected: Jan 1-31 for 1998, 1999, 2000 = 93 occurrences
     */
    public function testDailyInJanuaryThreeYears(): void
    {
        $event = new VEvent();
        $dtstart = GenericProperty::create('DTSTART', '19980101T090000');
        $dtstart->setParameter('TZID', 'America/New_York');
        $event->addProperty($dtstart);
        $event->setRrule('FREQ=DAILY;UNTIL=20000131T140000Z;BYMONTH=1');

        $occurrences = $this->expander->expandToArray($event);

        // 31 days * 3 years = 93
        $this->assertCount(93, $occurrences);
        // First occurrence
        $this->assertEquals('1998-01-01', $occurrences[0]->getStart()->format('Y-m-d'));
        // Last of 1998
        $this->assertEquals('1998-01-31', $occurrences[30]->getStart()->format('Y-m-d'));
        // First of 1999
        $this->assertEquals('1999-01-01', $occurrences[31]->getStart()->format('Y-m-d'));
        // Last of 1999
        $this->assertEquals('1999-01-31', $occurrences[61]->getStart()->format('Y-m-d'));
        // First of 2000
        $this->assertEquals('2000-01-01', $occurrences[62]->getStart()->format('Y-m-d'));
        // Last occurrence
        $this->assertEquals('2000-01-31', $occurrences[92]->getStart()->format('Y-m-d'));
    }
}
