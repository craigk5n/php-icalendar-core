<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use DateTimeImmutable;
use DateTimeZone;
use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\DateParser;
use Icalendar\Parser\ValueParser\DateTimeParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DateParser and DateTimeParser classes
 */
class DateTimeParserTest extends TestCase
{
    private DateParser $dateParser;
    private DateTimeParser $dateTimeParser;

    protected function setUp(): void
    {
        $this->dateParser = new DateParser();
        $this->dateTimeParser = new DateTimeParser();
    }

    // ==================== DateParser Tests ====================

    public function testParseDate(): void
    {
        $result = $this->dateParser->parse('20260205');

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2026-02-05', $result->format('Y-m-d'));
        $this->assertEquals('00:00:00', $result->format('H:i:s'));
    }

    public function testParseDateWithYearMonthDay(): void
    {
        $result = $this->dateParser->parse('19991231');

        $this->assertEquals('1999-12-31', $result->format('Y-m-d'));
    }

    public function testParseDateWithLeapYear(): void
    {
        // 2020 is a leap year
        $result = $this->dateParser->parse('20200229');

        $this->assertEquals('2020-02-29', $result->format('Y-m-d'));
    }

    public function testParseInvalidDateFormatTooShort(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format");

        $this->dateParser->parse('2026020');
    }

    public function testParseInvalidDateFormatTooLong(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format");

        $this->dateParser->parse('202602050');
    }

    public function testParseInvalidDateFormatNonNumeric(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format");

        $this->dateParser->parse('2026-02-05');
    }

    public function testParseInvalidDateMonthTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format");

        $this->dateParser->parse('20261301');
    }

    public function testParseInvalidDateDayTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format");

        $this->dateParser->parse('20260232');
    }

    public function testParseInvalidDateFebruaryInNonLeapYear(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format");

        // 2021 is not a leap year, February has 28 days
        $this->dateParser->parse('20210229');
    }

    public function testParseDateWithTzid(): void
    {
        $result = $this->dateParser->parse('20260205', ['TZID' => 'America/New_York']);

        $this->assertEquals('2026-02-05', $result->format('Y-m-d'));
        $this->assertEquals('America/New_York', $result->getTimezone()->getName());
    }

    public function testDateParserGetType(): void
    {
        $this->assertEquals('DATE', $this->dateParser->getType());
    }

    public function testDateParserCanParseValid(): void
    {
        $this->assertTrue($this->dateParser->canParse('20260205'));
        $this->assertTrue($this->dateParser->canParse('19991231'));
        $this->assertTrue($this->dateParser->canParse('20200229'));
    }

    public function testDateParserCanParseInvalid(): void
    {
        $this->assertFalse($this->dateParser->canParse('2026020'));   // Too short
        $this->assertFalse($this->dateParser->canParse('202602050')); // Too long
        $this->assertFalse($this->dateParser->canParse('2026-02-05')); // Non-numeric
        $this->assertFalse($this->dateParser->canParse('20261301'));  // Invalid month
        $this->assertFalse($this->dateParser->canParse('20260232'));  // Invalid day
    }

    // ==================== DateTimeParser Tests ====================

    public function testParseDateTimeLocal(): void
    {
        $result = $this->dateTimeParser->parse('20260205T100000');

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2026-02-05', $result->format('Y-m-d'));
        $this->assertEquals('10:00:00', $result->format('H:i:s'));
    }

    public function testParseDateTimeUtc(): void
    {
        $result = $this->dateTimeParser->parse('20260205T100000Z');

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2026-02-05', $result->format('Y-m-d'));
        $this->assertEquals('10:00:00', $result->format('H:i:s'));
        $this->assertEquals('UTC', $result->getTimezone()->getName());
    }

    public function testParseDateTimeWithTzid(): void
    {
        $result = $this->dateTimeParser->parse('20260205T100000', ['TZID' => 'America/New_York']);

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2026-02-05', $result->format('Y-m-d'));
        $this->assertEquals('10:00:00', $result->format('H:i:s'));
        $this->assertEquals('America/New_York', $result->getTimezone()->getName());
    }

    public function testParseDateTimeWithEuropeanTimezone(): void
    {
        $result = $this->dateTimeParser->parse('20260205T100000', ['TZID' => 'Europe/London']);

        $this->assertEquals('Europe/London', $result->getTimezone()->getName());
    }

    public function testParseDateTimeUtcConvertsTimezone(): void
    {
        // UTC time should be stored as UTC
        $result = $this->dateTimeParser->parse('20260205T150000Z');

        $this->assertEquals('UTC', $result->getTimezone()->getName());
        $this->assertEquals('15:00:00', $result->format('H:i:s'));
    }

    public function testParseInvalidDateTimeFormatTooShort(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format");

        $this->dateTimeParser->parse('20260205T10000');
    }

    public function testParseInvalidDateTimeFormatTooLong(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format");

        $this->dateTimeParser->parse('20260205T1000000');
    }

    public function testParseInvalidDateTimeFormatMissingT(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format");

        $this->dateTimeParser->parse('20260205100000');
    }

    public function testParseInvalidDateTimeHourTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format");

        $this->dateTimeParser->parse('20260205T250000');
    }

    public function testParseInvalidDateTimeMinuteTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format");

        $this->dateTimeParser->parse('20260205T106000');
    }

    public function testParseInvalidDateTimeSecondTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format");

        // Second=61 is invalid (60 is allowed for leap seconds)
        $this->dateTimeParser->parse('20260205T100061');
    }

    public function testParseInvalidDateTimeInvalidDate(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format");

        $this->dateTimeParser->parse('20260235T100000');
    }

    public function testParseDateTimeWithInvalidTimezone(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid timezone");

        $this->dateTimeParser->parse('20260205T100000', ['TZID' => 'Invalid/Timezone']);
    }

    public function testDateTimeParserGetType(): void
    {
        $this->assertEquals('DATE-TIME', $this->dateTimeParser->getType());
    }

    public function testDateTimeParserCanParseValidLocal(): void
    {
        $this->assertTrue($this->dateTimeParser->canParse('20260205T100000'));
    }

    public function testDateTimeParserCanParseValidUtc(): void
    {
        $this->assertTrue($this->dateTimeParser->canParse('20260205T100000Z'));
    }

    public function testDateTimeParserCanParseInvalid(): void
    {
        $this->assertFalse($this->dateTimeParser->canParse('20260205T10000'));    // Too short
        $this->assertFalse($this->dateTimeParser->canParse('20260205T1000000'));   // Too long
        $this->assertFalse($this->dateTimeParser->canParse('20260205T250000'));    // Invalid hour
        $this->assertFalse($this->dateTimeParser->canParse('20260205T106000'));    // Invalid minute
        $this->assertFalse($this->dateTimeParser->canParse('20260235T100000'));    // Invalid day
        $this->assertFalse($this->dateTimeParser->canParse('20260205 100000'));    // Space instead of T
    }

    public function testParseDateTimeEdgeCases(): void
    {
        // Midnight
        $result = $this->dateTimeParser->parse('20260205T000000');
        $this->assertEquals('00:00:00', $result->format('H:i:s'));

        // Just before midnight
        $result = $this->dateTimeParser->parse('20260205T235959');
        $this->assertEquals('23:59:59', $result->format('H:i:s'));

        // Leap year date
        $result = $this->dateTimeParser->parse('20200229T120000');
        $this->assertEquals('2020-02-29', $result->format('Y-m-d'));
    }

    public function testDateParserEdgeCases(): void
    {
        // First day of year
        $result = $this->dateParser->parse('20260101');
        $this->assertEquals('2026-01-01', $result->format('Y-m-d'));

        // Last day of year
        $result = $this->dateParser->parse('20261231');
        $this->assertEquals('2026-12-31', $result->format('Y-m-d'));

        // Month with 30 days
        $result = $this->dateParser->parse('20260430');
        $this->assertEquals('2026-04-30', $result->format('Y-m-d'));

        // Month with 31 days
        $result = $this->dateParser->parse('20260531');
        $this->assertEquals('2026-05-31', $result->format('Y-m-d'));
    }

    public function testCenturyLeapYear(): void
    {
        // 1900 is NOT a leap year (divisible by 100 but not by 400)
        $this->assertFalse($this->dateParser->canParse('19000229'));

        // 2000 IS a leap year (divisible by 400)
        $this->assertTrue($this->dateParser->canParse('20000229'));
    }
}
