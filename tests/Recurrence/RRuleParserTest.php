<?php

declare(strict_types=1);

namespace Icalendar\Tests\Recurrence;

use Icalendar\Exception\ParseException;
use Icalendar\Recurrence\RRule;
use Icalendar\Recurrence\RRuleParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RRuleParser and RRule value object
 */
class RRuleParserTest extends TestCase
{
    private RRuleParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RRuleParser();
        $this->parser->setStrict(true); // Default to strict mode for most tests
    }

    /** @test */
    public function testParseFreqDaily(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY');

        $this->assertInstanceOf(RRule::class, $rrule);
        $this->assertEquals('DAILY', $rrule->getFreq());
        $this->assertEquals(1, $rrule->getInterval());
        $this->assertFalse($rrule->hasCount());
        $this->assertFalse($rrule->hasUntil());
    }

    /** @test */
    public function testParseFreqWeekly(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY');

        $this->assertEquals('WEEKLY', $rrule->getFreq());
    }

    /** @test */
    public function testParseFreqMonthly(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY');

        $this->assertEquals('MONTHLY', $rrule->getFreq());
    }

    /** @test */
    public function testParseFreqYearly(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY');

        $this->assertEquals('YEARLY', $rrule->getFreq());
    }

    /** @test */
    public function testParseFreqSecondly(): void
    {
        $rrule = $this->parser->parse('FREQ=SECONDLY');

        $this->assertEquals('SECONDLY', $rrule->getFreq());
    }

    /** @test */
    public function testParseInterval(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;INTERVAL=2');

        $this->assertEquals('WEEKLY', $rrule->getFreq());
        $this->assertEquals(2, $rrule->getInterval());
    }

    /** @test */
    public function testParseCount(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=10');

        $this->assertTrue($rrule->hasCount());
        $this->assertEquals(10, $rrule->getCount());
        $this->assertFalse($rrule->hasUntil());
    }

    /** @test */
    public function testParseUntil(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;UNTIL=20261231T235959Z');

        $this->assertTrue($rrule->hasUntil());
        $this->assertNull($rrule->getCount());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rrule->getUntil());
        $this->assertEquals('2026-12-31T23:59:59+00:00', $rrule->getUntil()->format('c'));
    }

    /** @test */
    public function testParseUntilDate(): void
    {
        // Test UNTIL with just a date
        $rrule = $this->parser->parse('FREQ=DAILY;UNTIL=20261231');

        $this->assertTrue($rrule->hasUntil());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rrule->getUntil());
        $this->assertEquals('2026-12-31', $rrule->getUntil()->format('Y-m-d'));
    }

    /** @test */
    public function testParseUntilAndCountMutuallyExclusive(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('RRULE cannot have both UNTIL and COUNT');

        $this->parser->parse('FREQ=DAILY;UNTIL=20261231T235959Z;COUNT=10');
    }

    /** @test */
    public function testParseWkst(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;WKST=SU');

        $this->assertEquals('SU', $rrule->getWkst());
    }

    /** @test */
    public function testParseBySecond(): void
    {
        $rrule = $this->parser->parse('FREQ=MINUTELY;BYSECOND=0,30');

        $this->assertEquals([0, 30], $rrule->getBySecond());
    }

    /** @test */
    public function testParseByMinute(): void
    {
        $rrule = $this->parser->parse('FREQ=HOURLY;BYMINUTE=0,15,30,45');

        $this->assertEquals([0, 15, 30, 45], $rrule->getByMinute());
    }

    /** @test */
    public function testParseByHour(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;BYHOUR=9,17');

        $this->assertEquals([9, 17], $rrule->getByHour());
    }

    /** @test */
    public function testParseByDay(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR');

        $byDay = $rrule->getByDay();
        $this->assertCount(3, $byDay);
        $this->assertEquals(['day' => 'MO', 'ordinal' => null], $byDay[0]);
        $this->assertEquals(['day' => 'WE', 'ordinal' => null], $byDay[1]);
        $this->assertEquals(['day' => 'FR', 'ordinal' => null], $byDay[2]);
    }

    /** @test */
    public function testParseByDayWithOrdinal(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYDAY=2TU,-1FR');

        $byDay = $rrule->getByDay();
        $this->assertCount(2, $byDay);
        $this->assertEquals(['day' => 'TU', 'ordinal' => 2], $byDay[0]);
        $this->assertEquals(['day' => 'FR', 'ordinal' => -1], $byDay[1]);
    }

    /** @test */
    public function testParseByMonthDay(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=1,-1');

        $this->assertEquals([1, -1], $rrule->getByMonthDay());
    }

    /** @test */
    public function testParseByYearDay(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY;BYYEARDAY=1,100,200');

        $this->assertEquals([1, 100, 200], $rrule->getByYearDay());
    }

    /** @test */
    public function testParseByWeekNo(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY;BYWEEKNO=1,26,52');

        $this->assertEquals([1, 26, 52], $rrule->getByWeekNo());
    }

    /** @test */
    public function testParseByMonth(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY;BYMONTH=1,7');

        $this->assertEquals([1, 7], $rrule->getByMonth());
    }

    /** @test */
    public function testParseBySetPos(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1');

        $this->assertEquals([1, -1], $rrule->getBySetPos());
    }

    /** @test */
    public function testParseComplexRruleWithMultipleByComponents(): void
    {
        // Example combining several BY* rules
        $rruleString = 'FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=1;BYDAY=1MO,2TU';
        $rrule = $this->parser->parse($rruleString);

        $this->assertEquals('YEARLY', $rrule->getFreq());
        $this->assertEquals([1], $rrule->getByMonth());
        $this->assertEquals([1], $rrule->getByMonthDay());
        $byDay = $rrule->getByDay();
        $this->assertCount(2, $byDay);
        $this->assertEquals(['day' => 'MO', 'ordinal' => 1], $byDay[0]);
        $this->assertEquals(['day' => 'TU', 'ordinal' => 2], $byDay[1]);
    }

    /** @test */
    public function testParseRruleWithUntilAsDate(): void
    {
        // UNTIL specified as YYYYMMDD
        $rruleString = 'FREQ=DAILY;UNTIL=20261231';
        $rrule = $this->parser->parse($rruleString);

        $this->assertTrue($rrule->hasUntil());
        $this->assertEquals('2026-12-31', $rrule->getUntil()->format('Y-m-d'));
        // Check if time is defaulted or handled appropriately (should be midnight)
        $this->assertEquals('00:00:00', $rrule->getUntil()->format('H:i:s'));
    }

    /** @test */
    public function testParseRruleWithEmptyByParam(): void
    {
        // Test RRULEs with empty BY* parameters, e.g., BYSECOND=;
        // These should result in empty arrays for those components.
        $rruleString = 'FREQ=HOURLY;BYMINUTE=0;BYSECOND=;BYHOUR=9';
        $rrule = $this->parser->parse($rruleString);

        $this->assertEquals([0], $rrule->getByMinute());
        $this->assertEquals([], $rrule->getBySecond()); // Expect empty array for BYSECOND=
        $this->assertEquals([9], $rrule->getByHour());
    }

    /** @test */
    public function testParseRruleWithLeadingTrailingSemicolons(): void
    {
        // Test malformed RRULE strings with extra semicolons
        $rruleString = ';FREQ=DAILY;INTERVAL=1;';
        $rrule = $this->parser->parse($rruleString);

        $this->assertEquals('DAILY', $rrule->getFreq());
        $this->assertEquals(1, $rrule->getInterval());
    }

    /** @test */
    public function testParseRruleWithUnknownParamStrict(): void
    {
        // In strict mode, unknown parameters should cause a ParseException
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unknown parameter found in RRULE'); 

        $this->parser->parse('FREQ=DAILY;UNKNOWNPARAM=value');
    }
    
    /** @test */
    public function testParseRruleWithUnknownFreqLenient(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR FREQ value');

        $this->parser->parse('FREQ=WEEKLYLY'); // Invalid FREQ
    }

    /** @test */
    public function testParseRruleWithInvalidByDayOrdinal(): void
    {
        // Test invalid ordinal for BYDAY. 0 is invalid. 
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid BYDAY ordinal'); 

        $this->parser->parse('FREQ=MONTHLY;BYDAY=0TU');
    }

    /** @test */
    public function testRRuleToStringComplex(): void
    {
        // Test the toString method with a complex RRULE
        $freq = RRule::FREQ_WEEKLY;
        $interval = 2;
        $count = 10;
        $byDay = [['day' => 'MO', 'ordinal' => 1], ['day' => 'FR', 'ordinal' => null]];
        $wkst = RRule::DAY_SUNDAY;

        $rrule = new RRule(
            $freq,
            $interval,
            $count,
            null, // until
            [], [], [], // bySecond, byMinute, byHour
            $byDay,
            [], [], [], [], [], // byMonthDay, byYearDay, byWeekNo, byMonth, bySetPos
            $wkst
        );

        // Expecting: FREQ;INTERVAL;COUNT;BYDAY;WKST
        $expectedString = 'FREQ=WEEKLY;INTERVAL=2;COUNT=10;BYDAY=1MO,FR;WKST=SU';
        $this->assertEquals($expectedString, $rrule->toString());
    }
    
    /** @test */
    public function testRRuleToStringWithUntilDateTime(): void
    {
        $until = new \DateTimeImmutable('2026-12-31T23:59:59Z'); // UTC time
        $rrule = new RRule(
            'DAILY', 1, null, $until, [], [], [], [], [], [], [], [], [], 'MO'
        );
        // Format should be YYYYMMDDTHHMMSSZ for UTC
        $expectedString = 'FREQ=DAILY;UNTIL=20261231T235959Z';
        $this->assertEquals($expectedString, $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithUntilDate(): void
    {
        $until = new \DateTimeImmutable('2026-12-31'); // Date only
        $rrule = new RRule(
            'DAILY', 1, null, $until, [], [], [], [], [], [], [], [], [], 'MO', true
        );
        // Format should be YYYYMMDD
        $expectedString = 'FREQ=DAILY;UNTIL=20261231';
        $this->assertEquals($expectedString, $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithAllBYParams(): void
    {
        $rrule = new RRule(
            'YEARLY',
            1,
            null,
            null,
            [0, 30], // bySecond
            [15, 45], // byMinute
            [9, 17], // byHour
            [['day' => 'MO', 'ordinal' => 1], ['day' => 'FR', 'ordinal' => -1]], // byDay
            [1, 15, -1], // byMonthDay
            [1, 180, 365], // byYearDay
            [1, 52], // byWeekNo
            [1, 12], // byMonth
            [1, 5], // bySetPos
            'SU' // wkst
        );

        // Expect specific order from toString
        $expectedString = 'FREQ=YEARLY;BYSECOND=0,30;BYMINUTE=15,45;BYHOUR=9,17;BYDAY=1MO,-1FR;BYMONTHDAY=1,15,-1;BYYEARDAY=1,180,365;BYWEEKNO=1,52;BYMONTH=1,12;BYSETPOS=1,5;WKST=SU';
        $this->assertEquals($expectedString, $rrule->toString());
    }
}