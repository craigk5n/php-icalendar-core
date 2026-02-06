<?php

declare(strict_types=1);

namespace Icalendar\Tests\Recurrence;

use Icalendar\Recurrence\RecurrenceGenerator;
use Icalendar\Recurrence\RRuleParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RecurrenceGenerator
 */
class RecurrenceGeneratorTest extends TestCase
{
    private RecurrenceGenerator $generator;
    private RRuleParser $parser;

    protected function setUp(): void
    {
        $this->generator = new RecurrenceGenerator();
        $this->parser = new RRuleParser();
    }

    /** @test */
    public function testGenerateDailyInstances(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=10');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(10, $instances);
        $this->assertEquals('2026-01-01', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-10', $instances[9]->format('Y-m-d'));
        $this->assertEquals('09:00:00', $instances[0]->format('H:i:s'));
    }

    /** @test */
    public function testGenerateDailyWithCount(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=5');
        $dtstart = new \DateTimeImmutable('2026-03-15T10:30:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(5, $instances);
        $this->assertEquals('2026-03-15', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-03-19', $instances[4]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateDailyWithUntil(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;UNTIL=20260110T235959Z');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(10, $instances);
        $this->assertEquals('2026-01-01', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-10', $instances[9]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateWeeklyInstances(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;COUNT=5');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00'); // Thursday
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(5, $instances);
        $this->assertEquals('2026-01-01', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-08', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2026-01-15', $instances[2]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateWeeklyWithByday(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00'); // Thursday
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(10, $instances);
        // First week: Jan 2 (Fri), then Jan 5 (Mon), Jan 7 (Wed), Jan 9 (Fri), ...
        $this->assertEquals('2026-01-02', $instances[0]->format('Y-m-d')); // Friday
        $this->assertEquals('2026-01-05', $instances[1]->format('Y-m-d')); // Monday
        $this->assertEquals('2026-01-07', $instances[2]->format('Y-m-d')); // Wednesday
    }

    /** @test */
    public function testGenerateWeeklyWithInterval(): void
    {
        $rrule = $this->parser->parse('FREQ=WEEKLY;INTERVAL=2;COUNT=5');
        $dtstart = new \DateTimeImmutable('2026-01-05T09:00:00'); // Monday
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(5, $instances);
        $this->assertEquals('2026-01-05', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-19', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2026-02-02', $instances[2]->format('Y-m-d'));
        $this->assertEquals('2026-02-16', $instances[3]->format('Y-m-d'));
        $this->assertEquals('2026-03-02', $instances[4]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateMonthlyInstances(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;COUNT=6');
        $dtstart = new \DateTimeImmutable('2026-01-15T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(6, $instances);
        $this->assertEquals('2026-01-15', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-02-15', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2026-03-15', $instances[2]->format('Y-m-d'));
        $this->assertEquals('2026-04-15', $instances[3]->format('Y-m-d'));
        $this->assertEquals('2026-05-15', $instances[4]->format('Y-m-d'));
        $this->assertEquals('2026-06-15', $instances[5]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateMonthlyWithBydayOrdinal(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYDAY=2TU;COUNT=6');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(6, $instances);
        $this->assertEquals('2026-01-13', $instances[0]->format('Y-m-d')); // 2nd Tuesday
        $this->assertEquals('2026-02-10', $instances[1]->format('Y-m-d')); // 2nd Tuesday
        $this->assertEquals('2026-03-10', $instances[2]->format('Y-m-d')); // 2nd Tuesday
        $this->assertEquals('2026-04-14', $instances[3]->format('Y-m-d')); // 2nd Tuesday
        $this->assertEquals('2026-05-12', $instances[4]->format('Y-m-d')); // 2nd Tuesday
        $this->assertEquals('2026-06-09', $instances[5]->format('Y-m-d')); // 2nd Tuesday
    }

    /** @test */
    public function testGenerateMonthlyWithBymonthday(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=1,-1;COUNT=6');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(6, $instances);
        $this->assertEquals('2026-01-01', $instances[0]->format('Y-m-d')); // 1st
        $this->assertEquals('2026-01-31', $instances[1]->format('Y-m-d')); // last
        $this->assertEquals('2026-02-01', $instances[2]->format('Y-m-d')); // 1st
        $this->assertEquals('2026-02-28', $instances[3]->format('Y-m-d')); // last
        $this->assertEquals('2026-03-01', $instances[4]->format('Y-m-d')); // 1st
        $this->assertEquals('2026-03-31', $instances[5]->format('Y-m-d')); // last
    }

    /** @test */
    public function testGenerateMonthlyWithNegativeBymonthday(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=-1;COUNT=4');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(4, $instances);
        $this->assertEquals('2026-01-31', $instances[0]->format('Y-m-d')); // Jan 31
        $this->assertEquals('2026-02-28', $instances[1]->format('Y-m-d')); // Feb 28
        $this->assertEquals('2026-03-31', $instances[2]->format('Y-m-d')); // Mar 31
        $this->assertEquals('2026-04-30', $instances[3]->format('Y-m-d')); // Apr 30
    }

    /** @test */
    public function testGenerateYearlyInstances(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY;COUNT=5');
        $dtstart = new \DateTimeImmutable('2026-06-15T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(5, $instances);
        $this->assertEquals('2026-06-15', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2027-06-15', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2028-06-15', $instances[2]->format('Y-m-d'));
        $this->assertEquals('2029-06-15', $instances[3]->format('Y-m-d'));
        $this->assertEquals('2030-06-15', $instances[4]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateYearlyBymonthBymonthday(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=-1;COUNT=4');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(4, $instances);
        $this->assertEquals('2026-02-28', $instances[0]->format('Y-m-d')); // Feb 28
        $this->assertEquals('2027-02-28', $instances[1]->format('Y-m-d')); // Feb 28
        $this->assertEquals('2028-02-29', $instances[2]->format('Y-m-d')); // Feb 29 (leap year)
        $this->assertEquals('2029-02-28', $instances[3]->format('Y-m-d')); // Feb 28
    }

    /** @test */
    public function testGenerateWithBysetpos(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1;COUNT=6');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(6, $instances);
        // Last weekday of each month
        $this->assertEquals('2026-01-30', $instances[0]->format('Y-m-d')); // Fri
        $this->assertEquals('2026-02-27', $instances[1]->format('Y-m-d')); // Fri
        $this->assertEquals('2026-03-31', $instances[2]->format('Y-m-d')); // Tue
        $this->assertEquals('2026-04-30', $instances[3]->format('Y-m-d')); // Thu
    }

    /** @test */
    public function testGenerateWithExdate(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=5');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        $exdates = [
            new \DateTimeImmutable('2026-01-03T09:00:00'), // Exclude Jan 3 at same time as occurrence
        ];
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart, null, $exdates));
        
        $this->assertCount(5, $instances);
        // Jan 1, 2, 4, 5, 6 (skipping Jan 3, so count goes to 6)
        $this->assertEquals('2026-01-01', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-02', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2026-01-04', $instances[2]->format('Y-m-d'));
        $this->assertEquals('2026-01-05', $instances[3]->format('Y-m-d'));
        $this->assertEquals('2026-01-06', $instances[4]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateWithRdate(): void
    {
        // RDATE adds to occurrence set in chronological order
        // With RDATE on Jan 4, it should be yielded at position 3 (after Jan 1, 2, 3)
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=4');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        $rdates = [
            new \DateTimeImmutable('2026-01-04T09:00:00'), // Add Jan 4 (inserted in order)
        ];
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart, null, [], $rdates));
        
        $this->assertCount(4, $instances); // 3 from RRULE + 1 from RDATE (COUNT=4)
        $this->assertEquals('2026-01-01', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-02', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2026-01-03', $instances[2]->format('Y-m-d'));
        $this->assertEquals('2026-01-04', $instances[3]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateTimezonePreserved(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=3');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00', new \DateTimeZone('America/New_York'));
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(3, $instances);
        $this->assertEquals('America/New_York', $instances[0]->getTimezone()->getName());
        $this->assertEquals('09:00:00', $instances[0]->format('H:i:s'));
    }

    /** @test */
    public function testGenerateLeapYearFeb29(): void
    {
        $rrule = $this->parser->parse('FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=29;COUNT=3');
        $dtstart = new \DateTimeImmutable('2024-02-29T09:00:00'); // Leap year
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(3, $instances);
        $this->assertEquals('2024-02-29', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2028-02-29', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2032-02-29', $instances[2]->format('Y-m-d'));
    }

    /** @test */
    public function testGenerateMonthOverflow(): void
    {
        $rrule = $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=31;COUNT=6');
        $dtstart = new \DateTimeImmutable('2026-01-31T09:00:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(6, $instances);
        // Only months with 31 days
        $this->assertEquals('2026-01-31', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-03-31', $instances[1]->format('Y-m-d'));
        $this->assertEquals('2026-05-31', $instances[2]->format('Y-m-d'));
        $this->assertEquals('2026-07-31', $instances[3]->format('Y-m-d'));
        $this->assertEquals('2026-08-31', $instances[4]->format('Y-m-d'));
        $this->assertEquals('2026-10-31', $instances[5]->format('Y-m-d'));
    }

    /** @test */
    public function testGeneratorYieldsOneAtATime(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=1000');
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        
        $count = 0;
        foreach ($this->generator->generate($rrule, $dtstart) as $instance) {
            $count++;
            if ($count >= 10) {
                break; // Stop early to prove generator works
            }
        }
        
        $this->assertEquals(10, $count);
    }

    /** @test */
    public function testGeneratorStopsAtRangeEnd(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY'); // No COUNT or UNTIL
        $dtstart = new \DateTimeImmutable('2026-01-01T09:00:00');
        $rangeEnd = new \DateTimeImmutable('2026-01-10T23:59:59');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart, $rangeEnd));
        
        $this->assertCount(10, $instances);
        $this->assertEquals('2026-01-01', $instances[0]->format('Y-m-d'));
        $this->assertEquals('2026-01-10', $instances[9]->format('Y-m-d'));
    }

    /** @test */
    public function testComplexWeeklyPattern(): void
    {
        // Every other week on Monday, Wednesday, Friday
        $rrule = $this->parser->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;COUNT=10');
        $dtstart = new \DateTimeImmutable('2026-01-05T09:00:00'); // Monday
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(10, $instances);
        $this->assertEquals('2026-01-05', $instances[0]->format('Y-m-d')); // Mon
        $this->assertEquals('2026-01-07', $instances[1]->format('Y-m-d')); // Wed
        $this->assertEquals('2026-01-09', $instances[2]->format('Y-m-d')); // Fri
        $this->assertEquals('2026-01-19', $instances[3]->format('Y-m-d')); // Mon (next interval)
        $this->assertEquals('2026-01-21', $instances[4]->format('Y-m-d')); // Wed
    }

    /** @test */
    public function testFirstOccurrenceIsDtstart(): void
    {
        $rrule = $this->parser->parse('FREQ=DAILY;COUNT=5');
        $dtstart = new \DateTimeImmutable('2026-01-15T14:30:00');
        
        $instances = iterator_to_array($this->generator->generate($rrule, $dtstart));
        
        $this->assertCount(5, $instances);
        $this->assertEquals('2026-01-15', $instances[0]->format('Y-m-d'));
        $this->assertEquals('14:30:00', $instances[0]->format('H:i:s'));
    }
}