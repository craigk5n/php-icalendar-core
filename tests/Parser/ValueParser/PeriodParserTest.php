<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\PeriodParser;
use PHPUnit\Framework\TestCase;

class PeriodParserTest extends TestCase
{
    private PeriodParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PeriodParser();
    }

    public function testParseWithDateTimes(): void
    {
        $result = $this->parser->parse('19970101T230000Z/19970102T010000Z');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result[0]);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result[1]);
    }

    public function testParseWithDateTimeAndDuration(): void
    {
        $result = $this->parser->parse('19970101T230000Z/PT2H');

        $this->assertCount(2, $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result[0]);
        $this->assertInstanceOf(\DateInterval::class, $result[1]);
    }

    public function testParseLocalDateTimePeriod(): void
    {
        $result = $this->parser->parse('20260110T100000/20260110T120000');

        $this->assertCount(2, $result);
        $this->assertEquals('2026-01-10 10:00:00', $result[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-01-10 12:00:00', $result[1]->format('Y-m-d H:i:s'));
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty PERIOD value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseMissingSlash(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('missing slash separator');

        $this->parser->parse('19970101T230000Z19970102T010000Z');
    }

    public function testParseInvalidStartDateTime(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid PERIOD start');

        $this->parser->parse('invalid/19970102T010000Z');
    }

    public function testParseInvalidEndDateTime(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid PERIOD end');

        $this->parser->parse('19970101T230000Z/invalid');
    }

    public function testParseInvalidDuration(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('19970101T230000Z/INVALID');
    }

    public function testGetType(): void
    {
        $this->assertEquals('PERIOD', $this->parser->getType());
    }

    public function testCanParseWithDateTimes(): void
    {
        $this->assertTrue($this->parser->canParse('19970101T230000Z/19970102T010000Z'));
    }

    public function testCanParseWithDuration(): void
    {
        $this->assertTrue($this->parser->canParse('19970101T230000Z/PT2H'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseMissingSlash(): void
    {
        $this->assertFalse($this->parser->canParse('19970101T230000Z19970102T010000Z'));
    }
}
