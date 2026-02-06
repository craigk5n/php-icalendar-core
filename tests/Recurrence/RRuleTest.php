<?php

declare(strict_types=1);

namespace Icalendar\Tests\Recurrence;

use Icalendar\Recurrence\RRule;
use PHPUnit\Framework\TestCase;

class RRuleTest extends TestCase
{
    public function testCreateMinimalRRule(): void
    {
        $rrule = new RRule('DAILY');

        $this->assertEquals('DAILY', $rrule->getFreq());
        $this->assertEquals(1, $rrule->getInterval());
        $this->assertNull($rrule->getUntil());
        $this->assertNull($rrule->getCount());
        $this->assertEquals('MO', $rrule->getWkst());
    }

    public function testCreateRRuleWithAllParameters(): void
    {
        $until = new \DateTimeImmutable('2026-12-31T23:59:59Z');
        $rrule = new RRule(
            'WEEKLY',
            2,
            5,
            $until,
            [1, 5],
            [10, 20],
            [9, 17],
            [['day' => 'MO', 'ordinal' => null], ['day' => 'WE', 'ordinal' => null], ['day' => 'FR', 'ordinal' => null]],
            [1, 15],
            [],
            [],
            [1, 6, 12],
            [1]
        );

        $this->assertEquals('WEEKLY', $rrule->getFreq());
        $this->assertEquals(2, $rrule->getInterval());
        $this->assertEquals(5, $rrule->getCount());
        $this->assertSame($until, $rrule->getUntil());
        $this->assertEquals([1, 5], $rrule->getBySecond());
        $this->assertEquals([10, 20], $rrule->getByMinute());
        $this->assertEquals([9, 17], $rrule->getByHour());
        $this->assertEquals([['day' => 'MO', 'ordinal' => null], ['day' => 'WE', 'ordinal' => null], ['day' => 'FR', 'ordinal' => null]], $rrule->getByDay());
        $this->assertEquals([1, 15], $rrule->getByMonthDay());
        $this->assertEquals([1, 6, 12], $rrule->getByMonth());
        $this->assertEquals([1], $rrule->getBySetPos());
    }

    public function testFrequencyConstants(): void
    {
        $this->assertEquals('SECONDLY', RRule::FREQ_SECONDLY);
        $this->assertEquals('MINUTELY', RRule::FREQ_MINUTELY);
        $this->assertEquals('HOURLY', RRule::FREQ_HOURLY);
        $this->assertEquals('DAILY', RRule::FREQ_DAILY);
        $this->assertEquals('WEEKLY', RRule::FREQ_WEEKLY);
        $this->assertEquals('MONTHLY', RRule::FREQ_MONTHLY);
        $this->assertEquals('YEARLY', RRule::FREQ_YEARLY);
    }

    public function testWeekDayConstants(): void
    {
        $this->assertEquals('SU', RRule::DAY_SUNDAY);
        $this->assertEquals('MO', RRule::DAY_MONDAY);
        $this->assertEquals('TU', RRule::DAY_TUESDAY);
        $this->assertEquals('WE', RRule::DAY_WEDNESDAY);
        $this->assertEquals('TH', RRule::DAY_THURSDAY);
        $this->assertEquals('FR', RRule::DAY_FRIDAY);
        $this->assertEquals('SA', RRule::DAY_SATURDAY);
    }

    public function testEmptyByArrays(): void
    {
        $rrule = new RRule('DAILY');
        
        $this->assertEmpty($rrule->getBySecond());
        $this->assertEmpty($rrule->getByMinute());
        $this->assertEmpty($rrule->getByHour());
        $this->assertEmpty($rrule->getByDay());
        $this->assertEmpty($rrule->getByMonthDay());
        $this->assertEmpty($rrule->getByYearDay());
        $this->assertEmpty($rrule->getByWeekNo());
        $this->assertEmpty($rrule->getByMonth());
        $this->assertEmpty($rrule->getBySetPos());
    }

    public function testDefaultValues(): void
    {
        $rrule = new RRule('DAILY');
        
        $this->assertEquals('DAILY', $rrule->getFreq());
        $this->assertEquals(1, $rrule->getInterval());
        $this->assertNull($rrule->getUntil());
        $this->assertNull($rrule->getCount());
        $this->assertEquals('MO', $rrule->getWkst()); // Monday is default
    }

    public function testClassIsReadonly(): void
    {
        $reflection = new \ReflectionClass(RRule::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testWithComplexByDayStructure(): void
    {
        $rrule = new RRule(
            'MONTHLY',
            1,
            null,
            null,
            [],
            [],
            [],
            [
                ['day' => 'MO', 'ordinal' => 1], // First Monday
                ['day' => 'TU', 'ordinal' => 2], // Second Tuesday
                ['day' => 'FR', 'ordinal' => -1], // Last Friday
                ['day' => 'WE', 'ordinal' => null], // Every Wednesday
            ]
        );

        $byDay = $rrule->getByDay();
        $this->assertCount(4, $byDay);
        $this->assertEquals(['day' => 'MO', 'ordinal' => 1], $byDay[0]);
        $this->assertEquals(['day' => 'TU', 'ordinal' => 2], $byDay[1]);
        $this->assertEquals(['day' => 'FR', 'ordinal' => -1], $byDay[2]);
        $this->assertEquals(['day' => 'WE', 'ordinal' => null], $byDay[3]);
    }

    public function testWithNegativeMonthDays(): void
    {
        $rrule = new RRule('MONTHLY', 1, null, null, [], [], [], [], [-1, -2, -3]);

        $monthDays = $rrule->getByMonthDay();
        $this->assertEquals([-1, -2, -3], $monthDays);
    }

    public function testWithAllByArrays(): void
    {
        $rrule = new RRule(
            'YEARLY',
            1,
            null,
            null,
            [0, 30],           // BYSECOND
            [0, 15, 30],       // BYMINUTE  
            [9, 17],            // BYHOUR
            [],                  // BYDAY
            [1, 15],             // BYMONTHDAY
            [1, 100, 200],      // BYYEARDAY
            [1, 26],            // BYWEEKNO
            [1, 6, 12],         // BYMONTH
            [1]                  // BYSETPOS
        );

        $this->assertEquals([0, 30], $rrule->getBySecond());
        $this->assertEquals([0, 15, 30], $rrule->getByMinute());
        $this->assertEquals([9, 17], $rrule->getByHour());
        $this->assertEquals([1, 15], $rrule->getByMonthDay());
        $this->assertEquals([1, 100, 200], $rrule->getByYearDay());
        $this->assertEquals([1, 26], $rrule->getByWeekNo());
        $this->assertEquals([1, 6, 12], $rrule->getByMonth());
        $this->assertEquals([1], $rrule->getBySetPos());
    }
}