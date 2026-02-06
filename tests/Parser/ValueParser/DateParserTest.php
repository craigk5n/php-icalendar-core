<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use DateTimeImmutable;
use DateTimeZone;
use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\DateParser;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    private DateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DateParser();
    }

    // ========== Parse Tests ==========

    public function testParseSimpleDate(): void
    {
        $date = $this->parser->parse('20260206');
        
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertEquals('2026-02-06', $date->format('Y-m-d'));
        $this->assertEquals('00:00:00', $date->format('H:i:s'));
    }

    public function testParseLeapYear(): void
    {
        $date = $this->parser->parse('20240229');
        
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertEquals('2024-02-29', $date->format('Y-m-d'));
    }

    public function testParseNonLeapYear(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid DATE value');
        
        $this->parser->parse('20230229');
    }

    public function testParseDateWithTimezone(): void
    {
        $date = $this->parser->parse('20260206', ['TZID' => 'America/New_York']);
        
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertEquals('America/New_York', $date->getTimezone()->getName());
        $this->assertEquals('2026-02-06', $date->format('Y-m-d'));
        $this->assertEquals('00:00:00', $date->format('H:i:s'));
    }

    public function testParseDateWithUTCTimezone(): void
    {
        $date = $this->parser->parse('20260206', ['TZID' => 'UTC']);
        
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertEquals('UTC', $date->getTimezone()->getName());
    }

    public function testParseInvalidDateTooShort(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format: '202602'. Expected YYYYMMDD.");
        
        $this->parser->parse('202602');
    }

    public function testParseInvalidDateTooLong(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format: '202602066'. Expected YYYYMMDD.");
        
        $this->parser->parse('202602066');
    }

    public function testParseInvalidDateNonNumeric(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format: '2026-02-06'. Expected YYYYMMDD.");
        
        $this->parser->parse('2026-02-06');
    }

    public function testParseInvalidMonth(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid DATE value');
        
        $this->parser->parse('20261306');
    }

    public function testParseInvalidDay(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid DATE value');
        
        $this->parser->parse('20260231');
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid DATE format: ''. Expected YYYYMMDD.");
        
        $this->parser->parse('');
    }

    // ========== canParse Tests ==========

    public function testCanParseValidDate(): void
    {
        $this->assertTrue($this->parser->canParse('20260206'));
        $this->assertTrue($this->parser->canParse('20231231'));
        $this->assertTrue($this->parser->canParse('20240101'));
    }

    public function testCanParseInvalidDate(): void
    {
        $this->assertFalse($this->parser->canParse('202602'));
        $this->assertFalse($this->parser->canParse('202602066'));
        $this->assertFalse($this->parser->canParse('2026-02-06'));
        $this->assertFalse($this->parser->canParse('20261306'));
        $this->assertFalse($this->parser->canParse('20260231'));
        $this->assertFalse($this->parser->canParse('20230229'));
        $this->assertFalse($this->parser->canParse(''));
        $this->assertFalse($this->parser->canParse('abcdefg'));
    }

    public function testCanParseEdgeCases(): void
    {
        // Valid edge cases
        $this->assertTrue($this->parser->canParse('00010101')); // Minimum valid date
        $this->assertTrue($this->parser->canParse('99991231')); // Maximum valid date
        $this->assertTrue($this->parser->canParse('20000229')); // Leap year 2000
        
        // Invalid edge cases
        $this->assertFalse($this->parser->canParse('20260000')); // Day 00
        $this->assertFalse($this->parser->canParse('20260006')); // Month 00
        $this->assertFalse($this->parser->canParse('00010000')); // Day 00
        $this->assertFalse($this->parser->canParse('00000101')); // Month 00
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('DATE', $this->parser->getType());
    }

    // ========== Integration Tests ==========

    public function testParseCreatesImmutableDateTime(): void
    {
        $date = $this->parser->parse('20260206');
        
        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        
        // Verify it's truly immutable by attempting modification
        $modified = $date->modify('+1 day');
        $this->assertNotEquals($date, $modified);
        $this->assertEquals('2026-02-06', $date->format('Y-m-d'));
        $this->assertEquals('2026-02-07', $modified->format('Y-m-d'));
    }

    public function testParseAlwaysMidnight(): void
    {
        // Test with different timezones to ensure time is always midnight
        $dateUtc = $this->parser->parse('20260206', ['TZID' => 'UTC']);
        $dateNy = $this->parser->parse('20260206', ['TZID' => 'America/New_York']);
        $dateTokyo = $this->parser->parse('20260206', ['TZID' => 'Asia/Tokyo']);
        
        $this->assertEquals('00:00:00', $dateUtc->format('H:i:s'));
        $this->assertEquals('00:00:00', $dateNy->format('H:i:s'));
        $this->assertEquals('00:00:00', $dateTokyo->format('H:i:s'));
    }

    public function testParseTimezoneValidation(): void
    {
        $this->expectException(\Exception::class); // DateTimeZone throws Exception for invalid timezone
        
        $this->parser->parse('20260206', ['TZID' => 'Invalid/Timezone']);
    }
}