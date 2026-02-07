<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\TimeParser;
use PHPUnit\Framework\TestCase;

class TimeParserTest extends TestCase
{
    private TimeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TimeParser();
        $this->parser->setStrict(true);
    }

    public function testParseTimeWithoutZ(): void
    {
        $time = $this->parser->parse('100000');
        $this->assertInstanceOf(\DateTimeImmutable::class, $time);
        $this->assertEquals('10:00:00', $time->format('H:i:s'));
        $this->assertNull($time->getTimezone()); // Local time is floating
    }

    public function testParseTimeWithZ(): void
    {
        $time = $this->parser->parse('100000Z');
        $this->assertInstanceOf(\DateTimeImmutable::class, $time);
        $this->assertEquals('UTC', $time->getTimezone()->getName());
        $this->assertEquals('10:00:00', $time->format('H:i:s'));
    }

    public function testParseMidnight(): void
    {
        $time = $this->parser->parse('000000');
        $this->assertEquals('00:00:00', $time->format('H:i:s'));
    }

    public function testParseEndOfDay(): void
    {
        $time = $this->parser->parse('235960'); // Leap second allowed
        $this->assertEquals('23:59:60', $time->format('H:i:s'));
    }

    public function testParseLeapSecond(): void
    {
        $time = $this->parser->parse('235960Z');
        $this->assertEquals('UTC', $time->getTimezone()->getName());
        $this->assertEquals('23:59:60', $time->format('H:i:s'));
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty TIME value');
        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);
        $this->parser->parse('   ');
    }

    public function testParseTooShort(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid TIME format: '1000'. Expected HHMMSS or HHMMSSZ");
        $this->parser->parse('1000');
    }

    public function testParseTooLong(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid TIME format: '1000000'. Expected HHMMSS or HHMMSSZ");
        $this->parser->parse('1000000');
    }

    public function testParseInvalidHour(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid TIME: hours must be 00-23, got: 24");
        $this->parser->parse('240000');
    }

    public function testParseInvalidMinute(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid TIME: minutes must be 00-59, got: 60");
        $this->parser->parse('106000');
    }

    public function testParseInvalidSecond(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid TIME: seconds must be 00-60, got: 61");
        $this->parser->parse('100061');
    }

    public function testParseNonNumeric(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid TIME format: 'abc'");
        $this->parser->parse('abc');
    }

    public function testGetType(): void
    {
        $this->assertEquals('TIME', $this->parser->getType());
    }

    public function testCanParseValidWithoutZ(): void
    {
        $this->assertTrue($this->parser->canParse('100000'));
        $this->assertTrue($this->parser->canParse('235960'));
    }

    public function testCanParseValidWithZ(): void
    {
        $this->assertTrue($this->parser->canParse('100000Z'));
        $this->assertTrue($this->parser->canParse('235960Z'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseTooShort(): void
    {
        $this->assertFalse($this->parser->canParse('1000'));
    }

    public function testCanParseTooLong(): void
    {
        $this->assertFalse($this->parser->canParse('1000000'));
    }

    public function testCanParseNonNumeric(): void
    {
        $this->assertFalse($this->parser->canParse('abc'));
    }

    // Lenient mode test
    public function testParseLenientMode(): void
    {
        $this->parser->setStrict(false);
        $time = $this->parser->parse('10:00:00');
        $this->assertEquals('10:00:00', $time->format('H:i:s'));
    }
}