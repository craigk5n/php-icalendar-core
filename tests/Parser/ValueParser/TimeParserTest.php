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
    }

    public function testParseTimeWithoutZ(): void
    {
        $result = $this->parser->parse('143022');

        $this->assertEquals('14:30:22', $result->format('H:i:s'));
        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
    }

    public function testParseTimeWithZ(): void
    {
        $result = $this->parser->parse('143022Z');

        $this->assertEquals('14:30:22', $result->format('H:i:s'));
        $this->assertEquals('UTC', $result->getTimezone()->getName());
    }

    public function testParseMidnight(): void
    {
        $result = $this->parser->parse('000000');

        $this->assertEquals('00:00:00', $result->format('H:i:s'));
    }

    public function testParseEndOfDay(): void
    {
        $result = $this->parser->parse('235959');

        $this->assertEquals('23:59:59', $result->format('H:i:s'));
    }

    public function testParseLeapSecond(): void
    {
        $result = $this->parser->parse('235960');

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
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
        $this->expectExceptionMessage('Invalid TIME format');

        $this->parser->parse('14302');
    }

    public function testParseTooLong(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('1430223');
    }

    public function testParseInvalidHour(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('hours must be 00-23');

        $this->parser->parse('243022');
    }

    public function testParseInvalidMinute(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('minutes must be 00-59');

        $this->parser->parse('146022');
    }

    public function testParseInvalidSecond(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('seconds must be 00-60');

        $this->parser->parse('143099');
    }

    public function testParseNonNumeric(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('14:30:22');
    }

    public function testGetType(): void
    {
        $this->assertEquals('TIME', $this->parser->getType());
    }

    public function testCanParseValidWithoutZ(): void
    {
        $this->assertTrue($this->parser->canParse('143022'));
    }

    public function testCanParseValidWithZ(): void
    {
        $this->assertTrue($this->parser->canParse('143022Z'));
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
        $this->assertFalse($this->parser->canParse('14302'));
    }

    public function testCanParseTooLong(): void
    {
        $this->assertFalse($this->parser->canParse('1430223'));
    }

    public function testCanParseNonNumeric(): void
    {
        $this->assertFalse($this->parser->canParse('14:30:22'));
    }
}
