<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\DurationParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DurationParser class
 */
class DurationParserTest extends TestCase
{
    private DurationParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DurationParser();
    }

    public function testParseWeekDuration(): void
    {
        $result = $this->parser->parse('P3W');

        $this->assertEquals(21, $result->d);
        $this->assertEquals(0, $result->h);
        $this->assertEquals(0, $result->i);
        $this->assertEquals(0, $result->s);
        $this->assertEquals(0, $result->invert);
    }

    public function testParseDayDuration(): void
    {
        $result = $this->parser->parse('P5D');

        $this->assertEquals(5, $result->d);
        $this->assertEquals(0, $result->h);
        $this->assertEquals(0, $result->i);
        $this->assertEquals(0, $result->s);
    }

    public function testParseHourDuration(): void
    {
        $result = $this->parser->parse('PT2H');

        $this->assertEquals(2, $result->h);
        $this->assertEquals(0, $result->d);
        $this->assertEquals(0, $result->i);
        $this->assertEquals(0, $result->s);
    }

    public function testParseMinuteDuration(): void
    {
        $result = $this->parser->parse('PT30M');

        $this->assertEquals(30, $result->i);
        $this->assertEquals(0, $result->h);
        $this->assertEquals(0, $result->s);
    }

    public function testParseSecondDuration(): void
    {
        $result = $this->parser->parse('PT45S');

        $this->assertEquals(45, $result->s);
        $this->assertEquals(0, $result->i);
        $this->assertEquals(0, $result->h);
    }

    public function testParseFullDuration(): void
    {
        $result = $this->parser->parse('P1DT2H30M15S');

        $this->assertEquals(1, $result->d);
        $this->assertEquals(2, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(15, $result->s);
    }

    public function testParseDurationWithWeeksAndTime(): void
    {
        $result = $this->parser->parse('P2WT3H');

        $this->assertEquals(14, $result->d);
        $this->assertEquals(3, $result->h);
        $this->assertEquals(0, $result->i);
        $this->assertEquals(0, $result->s);
    }

    public function testParseNegativeWeekDuration(): void
    {
        $result = $this->parser->parse('-P4W');

        $this->assertEquals(28, $result->d);
        $this->assertEquals(1, $result->invert);
    }

    public function testParseNegativeDayDuration(): void
    {
        $result = $this->parser->parse('-P7D');

        $this->assertEquals(7, $result->d);
        $this->assertEquals(1, $result->invert);
    }

    public function testParseNegativeTimeDuration(): void
    {
        $result = $this->parser->parse('-PT1H30M');

        $this->assertEquals(1, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(1, $result->invert);
    }

    public function testParseZeroDuration(): void
    {
        $result = $this->parser->parse('P0D');

        $this->assertEquals(0, $result->d);
    }

    public function testParseEmptyDuration(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty DURATION value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseMissingP(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('must start with P');

        $this->parser->parse('3D');
    }

    public function testParseInvalidFormat(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('invalid');
    }

    public function testParseInvalidDateComponent(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('P1Y');
    }

    public function testParseInvalidTimeComponent(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('PT30');
    }

    public function testParseEmptyAfterP(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('missing duration components');

        $this->parser->parse('P');
    }

    public function testParsePartialTime(): void
    {
        $result = $this->parser->parse('PT1H30M');

        $this->assertEquals(1, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(0, $result->s);
    }

    public function testParseLargeDuration(): void
    {
        $result = $this->parser->parse('P365D');

        $this->assertEquals(365, $result->d);
    }

    public function testParseDurationWithParameters(): void
    {
        $result = $this->parser->parse('P1D', ['TZID' => 'America/New_York']);

        $this->assertEquals(1, $result->d);
    }

    public function testGetType(): void
    {
        $this->assertEquals('DURATION', $this->parser->getType());
    }

    public function testCanParseValidWeekDuration(): void
    {
        $this->assertTrue($this->parser->canParse('P3W'));
    }

    public function testCanParseValidDayDuration(): void
    {
        $this->assertTrue($this->parser->canParse('P5D'));
    }

    public function testCanParseValidTimeDuration(): void
    {
        $this->assertTrue($this->parser->canParse('PT2H30M'));
    }

    public function testCanParseValidFullDuration(): void
    {
        $this->assertTrue($this->parser->canParse('P1DT2H30M15S'));
    }

    public function testCanParseNegativeDuration(): void
    {
        $this->assertTrue($this->parser->canParse('-P3W'));
    }

    public function testCanParseInvalidMissingP(): void
    {
        $this->assertFalse($this->parser->canParse('3D'));
    }

    public function testCanParseInvalidEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseInvalidWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseInvalidFormat(): void
    {
        $this->assertFalse($this->parser->canParse('invalid'));
    }

    public function testCanParseOnlyP(): void
    {
        $this->assertFalse($this->parser->canParse('P'));
    }

    public function testCanParsePartialTime(): void
    {
        $this->assertTrue($this->parser->canParse('PT1H30M'));
    }

    public function testDateIntervalProperties(): void
    {
        $result = $this->parser->parse('P1DT2H30M45S');

        $this->assertEquals(0, $result->y);
        $this->assertEquals(0, $result->m);
        $this->assertEquals(1, $result->d);
        $this->assertEquals(2, $result->h);
        $this->assertEquals(30, $result->i);
        $this->assertEquals(45, $result->s);
    }
}
