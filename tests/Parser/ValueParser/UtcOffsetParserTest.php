<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\UtcOffsetParser;
use PHPUnit\Framework\TestCase;

class UtcOffsetParserTest extends TestCase
{
    private UtcOffsetParser $parser;

    protected function setUp(): void
    {
        $this->parser = new UtcOffsetParser();
    }

    public function testParsePositiveOffset(): void
    {
        $result = $this->parser->parse('+0530');

        $this->assertEquals(5, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(0, $result->invert);
    }

    public function testParseNegativeOffset(): void
    {
        $result = $this->parser->parse('-0530');

        $this->assertEquals(5, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(1, $result->invert);
    }

    public function testParseZeroOffset(): void
    {
        $result = $this->parser->parse('+0000');

        $this->assertEquals(0, $result->h);
        $this->assertEquals(0, $result->i);
        $this->assertEquals(0, $result->invert);
    }

    public function testParsePositiveOffsetWithSeconds(): void
    {
        $result = $this->parser->parse('+053045');

        $this->assertEquals(5, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(45, $result->s);
    }

    public function testParseNegativeOffsetWithSeconds(): void
    {
        $result = $this->parser->parse('-053045');

        $this->assertEquals(5, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(45, $result->s);
        $this->assertEquals(1, $result->invert);
    }

    public function testParsePositiveHourOnly(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid UTC-OFFSET format');

        $this->parser->parse('+05');
    }

    public function testParseNegativeHourOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('-12');
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty UTC-OFFSET value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseMissingSign(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid UTC-OFFSET format');

        $this->parser->parse('0530');
    }

    public function testParseInvalidHour(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('hours must be 00-23');

        $this->parser->parse('+2400');
    }

    public function testParseInvalidMinute(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('minutes must be 00-59');

        $this->parser->parse('+0560');
    }

    public function testParseInvalidSecond(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('seconds must be 00-59');

        $this->parser->parse('+053060');
    }

    public function testGetType(): void
    {
        $this->assertEquals('UTC-OFFSET', $this->parser->getType());
    }

    public function testCanParseValidPositive(): void
    {
        $this->assertTrue($this->parser->canParse('+0530'));
    }

    public function testCanParseValidNegative(): void
    {
        $this->assertTrue($this->parser->canParse('-0530'));
    }

    public function testCanParseWithSeconds(): void
    {
        $this->assertTrue($this->parser->canParse('+053045'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseMissingSign(): void
    {
        $this->assertFalse($this->parser->canParse('0530'));
    }
}
