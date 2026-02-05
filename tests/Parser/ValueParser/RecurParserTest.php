<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\RecurParser;
use PHPUnit\Framework\TestCase;

class RecurParserTest extends TestCase
{
    private RecurParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RecurParser();
    }

    public function testParseDailyFrequency(): void
    {
        $result = $this->parser->parse('FREQ=DAILY');

        $this->assertEquals('DAILY', $result['FREQ']);
    }

    public function testParseWeeklyFrequency(): void
    {
        $result = $this->parser->parse('FREQ=WEEKLY');

        $this->assertEquals('WEEKLY', $result['FREQ']);
    }

    public function testParseMonthlyFrequency(): void
    {
        $result = $this->parser->parse('FREQ=MONTHLY');

        $this->assertEquals('MONTHLY', $result['FREQ']);
    }

    public function testParseYearlyFrequency(): void
    {
        $result = $this->parser->parse('FREQ=YEARLY');

        $this->assertEquals('YEARLY', $result['FREQ']);
    }

    public function testParseComplexRrule(): void
    {
        $result = $this->parser->parse('FREQ=WEEKLY;INTERVAL=2;COUNT=10;BYDAY=MO,WE,FR');

        $this->assertEquals('WEEKLY', $result['FREQ']);
        $this->assertEquals('2', $result['INTERVAL']);
        $this->assertEquals('10', $result['COUNT']);
        $this->assertEquals('MO,WE,FR', $result['BYDAY']);
    }

    public function testParseWithUntil(): void
    {
        $result = $this->parser->parse('FREQ=DAILY;UNTIL=20261231T235959Z');

        $this->assertEquals('DAILY', $result['FREQ']);
        $this->assertEquals('20261231T235959Z', $result['UNTIL']);
    }

    public function testParseWithByDay(): void
    {
        $result = $this->parser->parse('FREQ=WEEKLY;BYDAY=SU');

        $this->assertEquals('WEEKLY', $result['FREQ']);
        $this->assertEquals('SU', $result['BYDAY']);
    }

    public function testParseWithByDayMultiple(): void
    {
        $result = $this->parser->parse('FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR');

        $this->assertEquals('MO,TU,WE,TH,FR', $result['BYDAY']);
    }

    public function testParseWithByMonth(): void
    {
        $result = $this->parser->parse('FREQ=YEARLY;BYMONTH=1,7');

        $this->assertEquals('YEARLY', $result['FREQ']);
        $this->assertEquals('1,7', $result['BYMONTH']);
    }

    public function testParseWithWkst(): void
    {
        $result = $this->parser->parse('FREQ=WEEKLY;WKST=MO');

        $this->assertEquals('MO', $result['WKST']);
    }

    public function testParseByDayWithOrdinal(): void
    {
        $result = $this->parser->parse('FREQ=MONTHLY;BYDAY=1MO,-1TU');

        $this->assertEquals('1MO,-1TU', $result['BYDAY']);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty RECUR value');

        $this->parser->parse('');
    }

    public function testParseMissingFreq(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('must have FREQ component');

        $this->parser->parse('COUNT=10');
    }

    public function testParseInvalidFreq(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR FREQ value');

        $this->parser->parse('FREQ=DAILYLY');
    }

    public function testParseInvalidComponent(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR component');

        $this->parser->parse('FREQ=DAILY;INVALID=value');
    }

    public function testParseInvalidCount(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR COUNT value');

        $this->parser->parse('FREQ=DAILY;COUNT=-5');
    }

    public function testParseInvalidByDay(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYDAY value');

        $this->parser->parse('FREQ=DAILY;BYDAY=XYZ');
    }

    public function testParseInvalidByMonth(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYMONTH value');

        $this->parser->parse('FREQ=YEARLY;BYMONTH=13');
    }

    public function testGetType(): void
    {
        $this->assertEquals('RECUR', $this->parser->getType());
    }

    public function testCanParseDaily(): void
    {
        $this->assertTrue($this->parser->canParse('FREQ=DAILY'));
    }

    public function testCanParseComplex(): void
    {
        $this->assertTrue($this->parser->canParse('FREQ=WEEKLY;INTERVAL=2;COUNT=10;BYDAY=MO,WE,FR'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseMissingFreq(): void
    {
        $this->assertFalse($this->parser->canParse('COUNT=10'));
    }

    public function testCanParseInvalidComponent(): void
    {
        $this->assertFalse($this->parser->canParse('FREQ=DAILY;INVALID=value'));
    }

    // ========== New validation tests ==========

    public function testParseUntilAndCountMutuallyExclusive(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('RECUR cannot have both UNTIL and COUNT');

        $this->parser->parse('FREQ=DAILY;UNTIL=20261231T235959Z;COUNT=10');
    }

    public function testParseBySecond(): void
    {
        $result = $this->parser->parse('FREQ=MINUTELY;BYSECOND=0,30');

        $this->assertEquals('0,30', $result['BYSECOND']);
    }

    public function testParseBySecondInvalid(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYSECOND value');

        $this->parser->parse('FREQ=MINUTELY;BYSECOND=61');
    }

    public function testParseByMinute(): void
    {
        $result = $this->parser->parse('FREQ=HOURLY;BYMINUTE=0,15,30,45');

        $this->assertEquals('0,15,30,45', $result['BYMINUTE']);
    }

    public function testParseByMinuteInvalid(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYMINUTE value');

        $this->parser->parse('FREQ=HOURLY;BYMINUTE=60');
    }

    public function testParseByHour(): void
    {
        $result = $this->parser->parse('FREQ=DAILY;BYHOUR=9,17');

        $this->assertEquals('9,17', $result['BYHOUR']);
    }

    public function testParseByHourInvalid(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYHOUR value');

        $this->parser->parse('FREQ=DAILY;BYHOUR=24');
    }

    public function testParseByMonthDayNegative(): void
    {
        // Negative values are valid - last day of month
        $result = $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=-1');

        $this->assertEquals('-1', $result['BYMONTHDAY']);
    }

    public function testParseByMonthDayNegativeMultiple(): void
    {
        $result = $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=1,-1');

        $this->assertEquals('1,-1', $result['BYMONTHDAY']);
    }

    public function testParseByMonthDayInvalidZero(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYMONTHDAY value');

        $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=0');
    }

    public function testParseByMonthDayInvalidRange(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYMONTHDAY value');

        $this->parser->parse('FREQ=MONTHLY;BYMONTHDAY=32');
    }

    public function testParseByYearDay(): void
    {
        $result = $this->parser->parse('FREQ=YEARLY;BYYEARDAY=1,100,200');

        $this->assertEquals('1,100,200', $result['BYYEARDAY']);
    }

    public function testParseByYearDayNegative(): void
    {
        $result = $this->parser->parse('FREQ=YEARLY;BYYEARDAY=-1,-100');

        $this->assertEquals('-1,-100', $result['BYYEARDAY']);
    }

    public function testParseByYearDayInvalid(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYYEARDAY value');

        $this->parser->parse('FREQ=YEARLY;BYYEARDAY=367');
    }

    public function testParseByWeekNo(): void
    {
        $result = $this->parser->parse('FREQ=YEARLY;BYWEEKNO=1,26,52');

        $this->assertEquals('1,26,52', $result['BYWEEKNO']);
    }

    public function testParseByWeekNoNegative(): void
    {
        $result = $this->parser->parse('FREQ=YEARLY;BYWEEKNO=-1');

        $this->assertEquals('-1', $result['BYWEEKNO']);
    }

    public function testParseByWeekNoInvalid(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYWEEKNO value');

        $this->parser->parse('FREQ=YEARLY;BYWEEKNO=54');
    }

    public function testParseBySetPos(): void
    {
        $result = $this->parser->parse('FREQ=MONTHLY;BYDAY=MO;BYSETPOS=-1');

        $this->assertEquals('-1', $result['BYSETPOS']);
    }

    public function testParseBySetPosMultiple(): void
    {
        $result = $this->parser->parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1');

        $this->assertEquals('1,-1', $result['BYSETPOS']);
    }

    public function testParseBySetPosInvalid(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYSETPOS value');

        $this->parser->parse('FREQ=MONTHLY;BYSETPOS=367');
    }

    public function testParseByDayOrdinalInvalidZero(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYDAY ordinal');

        $this->parser->parse('FREQ=MONTHLY;BYDAY=0MO');
    }

    public function testParseByDayOrdinalInvalidRange(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid RECUR BYDAY ordinal');

        $this->parser->parse('FREQ=MONTHLY;BYDAY=54MO');
    }

    public function testParseByDayPositiveOrdinal(): void
    {
        $result = $this->parser->parse('FREQ=MONTHLY;BYDAY=+2TU');

        $this->assertEquals('+2TU', $result['BYDAY']);
    }
}
