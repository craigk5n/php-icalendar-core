<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use DateTimeImmutable;
use DateTimeZone;
use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\DateTimeParser;
use PHPUnit\Framework\TestCase;

class DateTimeParserTest extends TestCase
{
    private DateTimeParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new DateTimeParser();
        $this->parser->setStrict(true);
    }

    public function testParseDateTimeLocal(): void
    {
        $dt = $this->parser->parse('20260206T100000');
        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertEquals('2026-02-06 10:00:00', $dt->format('Y-m-d H:i:s'));
        // Local times should have the default system timezone.
        $this->assertEquals(date_default_timezone_get(), $dt->getTimezone()->getName()); 
    }

    public function testParseDateTimeUtc(): void
    {
        $dt = $this->parser->parse('20260206T100000Z');
        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertEquals('UTC', $dt->getTimezone()->getName());
        $this->assertEquals('2026-02-06 10:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testParseDateTimeWithTzid(): void
    {
        $dt = $this->parser->parse('20260206T090000', ['TZID' => 'America/New_York']);
        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertEquals('America/New_York', $dt->getTimezone()->getName());
        $this->assertEquals('2026-02-06 09:00:00', $dt->format('Y-m-d H:i:s'));
    }

    public function testParseDateTimeWithEuropeanTimezone(): void
    {
        $dt = $this->parser->parse('20260206T100000', ['TZID' => 'Europe/Berlin']);
        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertEquals('Europe/Berlin', $dt->getTimezone()->getName());
    }

    public function testParseDateTimeUtcConvertsTimezone(): void
    {
        $dt = $this->parser->parse('20260206T100000Z'); // UTC
        $dt = $dt->setTimezone(new DateTimeZone('America/New_York')); // Convert to NY time
        $this->assertEquals('2026-02-06 05:00:00', $dt->format('Y-m-d H:i:s')); // NY is UTC-5
    }

    public function testParseInvalidDateTimeFormatTooShort(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format: '20260206T1000'. Expected YYYYMMDDTHHMMSS[Z].");
        $this->parser->parse('20260206T1000');
    }

    public function testParseInvalidDateTimeFormatTooLong(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format: '20260206T1000000'. Expected YYYYMMDDTHHMMSS[Z].");
        $this->parser->parse('20260206T1000000');
    }

    public function testParseInvalidDateTimeFormatMissingT(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME format: '20260206100000'. Expected YYYYMMDDTHHMMSS[Z].");
        $this->parser->parse('20260206100000');
    }

    public function testParseInvalidDateTimeHourTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME value: '20260206T240000'");
        $this->parser->parse('20260206T240000');
    }

    public function testParseInvalidDateTimeMinuteTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME value: '20260206T146000'");
        $this->parser->parse('20260206T146000');
    }

    public function testParseInvalidDateTimeSecondTooHigh(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME value: '20260206T143099'");
        $this->parser->parse('20260206T143099');
    }

    public function testParseInvalidDateTimeInvalidDate(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE-TIME value: '20260230T100000'");
        $this->parser->parse('20260230T100000');
    }

    public function testParseDateTimeWithInvalidTimezone(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid timezone: 'Invalid/Timezone'");
        $this->parser->parse('20260206T100000', ['TZID' => 'Invalid/Timezone']);
    }

    public function testDateTimeParserGetType(): void
    {
        $this->assertEquals('DATE-TIME', $this->parser->getType());
    }

    public function testDateTimeParserCanParseValidLocal(): void
    {
        $this->assertTrue($this->parser->canParse('20260206T100000'));
    }

    public function testDateTimeParserCanParseValidUtc(): void
    {
        $this->assertTrue($this->parser->canParse('20260206T100000Z'));
    }

    public function testDateTimeParserCanParseInvalid(): void
    {
        $this->assertFalse($this->parser->canParse('20260206')); // DATE is not DATE-TIME
        $this->assertFalse($this->parser->canParse('20260206T1000')); // Too short
        $this->assertFalse($this->parser->canParse('20260206T1000000')); // Too long
        $this->assertFalse($this->parser->canParse('20260206100000')); // Missing T
        $this->assertFalse($this->parser->canParse(''));
        $this->assertFalse($this->parser->canParse('   '));
    }
    
    // Lenient mode test
    public function testParseLenientMode(): void
    {
        $this->parser->setStrict(false);
        // PHP's DateTimeImmutable can parse this format, which is less strict than RFC
        $dt = $this->parser->parse('2026-02-06 10:00:00'); 
        $this->assertInstanceOf(DateTimeImmutable::class, $dt); // Ensure it's an immutable DateTime object
        $this->assertEquals('2026-02-06 10:00:00', $dt->format('Y-m-d H:i:s'));
    }
}
