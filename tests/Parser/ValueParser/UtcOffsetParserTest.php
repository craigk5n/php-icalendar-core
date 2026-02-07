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
        $this->parser->setStrict(true);
    }

    public function testParsePositiveOffset(): void
    {
        $interval = $this->parser->parse('+0500');
        $this->assertEquals(5, $interval->h);
        $this->assertEquals(0, $interval->i);
        $this->assertEquals(0, $interval->s);
        $this->assertEquals(0, $interval->invert);
    }

    public function testParseNegativeOffset(): void
    {
        $interval = $this->parser->parse('-0500');
        $this->assertEquals(5, $interval->h);
        $this->assertEquals(0, $interval->i);
        $this->assertEquals(0, $interval->s);
        $this->assertEquals(1, $interval->invert);
    }

    public function testParseZeroOffset(): void
    {
        $interval = $this->parser->parse('+0000');
        $this->assertEquals(0, $interval->h);
        $this->assertEquals(0, $interval->i);
        $this->assertEquals(0, $interval->s);
        $this->assertEquals(0, $interval->invert);
    }

    public function testParsePositiveOffsetWithSeconds(): void
    {
        $interval = $this->parser->parse('+053015');
        $this->assertEquals(5, $interval->h);
        $this->assertEquals(30, $interval->i);
        $this->assertEquals(15, $interval->s);
        $this->assertEquals(0, $interval->invert);
    }

    public function testParseNegativeOffsetWithSeconds(): void
    {
        $interval = $this->parser->parse('-053015');
        $this->assertEquals(5, $interval->h);
        $this->assertEquals(30, $interval->i);
        $this->assertEquals(15, $interval->s);
        $this->assertEquals(1, $interval->invert);
    }

    public function testParsePositiveHourOnly(): void
    {
        $interval = $this->parser->parse('+0500');
        $this->assertEquals(5, $interval->h);
        $this->assertEquals(0, $interval->i);
        $this->assertEquals(0, $interval->s);
    }

    public function testParseNegativeHourOnly(): void
    {
        $interval = $this->parser->parse('-0500');
        $this->assertEquals(5, $interval->h);
        $this->assertEquals(0, $interval->i);
        $this->assertEquals(0, $interval->s);
        $this->assertEquals(1, $interval->invert);
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
        $this->parser->parse('0500');
    }

    public function testParseInvalidHour(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid UTC-OFFSET: hours must be 00-23, got: 24");
        $this->parser->parse('+2400');
    }

    public function testParseInvalidMinute(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid UTC-OFFSET: minutes must be 00-59, got: 60");
        $this->parser->parse('+0560');
    }

    public function testParseInvalidSecond(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid UTC-OFFSET: seconds must be 00-59, got: 60");
        $this->parser->parse('+053060');
    }

    public function getType(): string
    {
        return 'UTC-OFFSET';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);
        if ($value === '') return false;
        return (bool) preg_match('/^[+-]\d{4}(?:\d{2})?$/', $value);
    }
}