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
        $rrule = $this->parser->parse('FREQ=DAILY;UNTIL=20261231');

        $this->assertTrue($rrule->hasUntil());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rrule->getUntil());
        $this->assertEquals('2026-12-31', $rrule->getUntil()->format('Y-m-d'));
    }

    /** @test */
    public function testParseUntilAndCountMutuallyExclusive(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('cannot have both UNTIL and COUNT');

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
    public function testParseComplexRrule(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;INTERVAL=2;COUNT=10;BYDAY=MO,WE,FR;WKST=SU');

        $this->assertEquals('WEEKLY', $rrule->getFreq());
        $this->assertEquals(2, $rrule->getInterval());
        $this->assertEquals(10, $rrule->getCount());
        $this->assertEquals('SU', $rrule->getWkst());

        $byDay = $rrule->getByDay();
        $this->assertCount(3, $byDay);
    }

    /** @test */
    public function testCanParseValidRrule(): void
    {
        $this->assertTrue($this->parser->canParse('FREQ=DAILY'));
        $this->assertTrue($this->parser->canParse('FREQ=WEEKLY;INTERVAL=2;COUNT=10;BYDAY=MO,WE,FR'));
    }

    /** @test */
    public function testCanParseInvalidRrule(): void
    {
        $this->assertFalse($this->parser->canParse(''));
        $this->assertFalse($this->parser->canParse('COUNT=10'));
        $this->assertFalse($this->parser->canParse('FREQ=DAILY;INVALID=value'));
    }

    /** @test */
    public function testParseMissingFreq(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('must have FREQ component');

        $this->parser->parse('COUNT=10');
    }

    /** @test */
    public function testParseInvalidFreq(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR FREQ value');

        $this->parser->parse('FREQ=DAILYLY');
    }

    /** @test */
    public function testRRuleIsImmutable(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=10');

        // Create new instance with modified values
        $newRrule = $rrule->withFreq('WEEKLY');

        // Original should not change
        $this->assertEquals('DAILY', $rrule->getFreq());
        $this->assertEquals('WEEKLY', $newRrule->getFreq());

        // Other values should be preserved
        $this->assertEquals(10, $newRrule->getCount());
    }

    /** @test */
    public function testRRuleToStringBasic(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY');

        $this->assertEquals('FREQ=DAILY', $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithInterval(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;INTERVAL=2');

        $this->assertEquals('FREQ=WEEKLY;INTERVAL=2', $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithCount(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=10');

        $this->assertEquals('FREQ=DAILY;COUNT=10', $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithByDay(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR');

        $this->assertEquals('FREQ=WEEKLY;BYDAY=MO,WE,FR', $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithByDayOrdinal(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYDAY=2TU,-1FR');

        $this->assertEquals('FREQ=MONTHLY;BYDAY=2TU,-1FR', $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithByMonth(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY;BYMONTH=1,7');

        $this->assertEquals('FREQ=YEARLY;BYMONTH=1,7', $rrule->toString());
    }

    /** @test */
    public function testRRuleToStringWithWkst(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;WKST=SU');

        $this->assertEquals('FREQ=WEEKLY;WKST=SU', $rrule->toString());
    }

    /** @test */
    public function testRRuleWithInterval(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY');
        $newRrule = $rrule->withInterval(3);

        $this->assertEquals(3, $newRrule->getInterval());
        $this->assertEquals(1, $rrule->getInterval()); // Original unchanged
    }

    /** @test */
    public function testRRuleWithCount(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY');
        $newRrule = $rrule->withCount(5);

        $this->assertEquals(5, $newRrule->getCount());
        $this->assertTrue($newRrule->hasCount());
    }

    /** @test */
    public function testRRuleWithUntil(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY');
        $until = new \DateTimeImmutable('2026-12-31 23:59:59');
        $newRrule = $rrule->withUntil($until);

        $this->assertTrue($newRrule->hasUntil());
        $this->assertEquals($until, $newRrule->getUntil());
    }

    /** @test */
    public function testRRuleConstants(): void
    {
        $this->assertEquals('SECONDLY', RRule::FREQ_SECONDLY);
        $this->assertEquals('MINUTELY', RRule::FREQ_MINUTELY);
        $this->assertEquals('HOURLY', RRule::FREQ_HOURLY);
        $this->assertEquals('DAILY', RRule::FREQ_DAILY);
        $this->assertEquals('WEEKLY', RRule::FREQ_WEEKLY);
        $this->assertEquals('MONTHLY', RRule::FREQ_MONTHLY);
        $this->assertEquals('YEARLY', RRule::FREQ_YEARLY);

        $this->assertEquals('SU', RRule::DAY_SUNDAY);
        $this->assertEquals('MO', RRule::DAY_MONDAY);
        $this->assertEquals('TU', RRule::DAY_TUESDAY);
        $this->assertEquals('WE', RRule::DAY_WEDNESDAY);
        $this->assertEquals('TH', RRule::DAY_THURSDAY);
        $this->assertEquals('FR', RRule::DAY_FRIDAY);
        $this->assertEquals('SA', RRule::DAY_SATURDAY);
    }
}