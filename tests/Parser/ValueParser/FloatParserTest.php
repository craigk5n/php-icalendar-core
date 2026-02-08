<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\FloatParser;
use PHPUnit\Framework\TestCase;

class FloatParserTest extends TestCase
{
    private FloatParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new FloatParser();
    }

    public function testParsePositiveFloat(): void
    {
        $result = $this->parser->parse('3.14');

        $this->assertEquals(3.14, $result);
        $this->assertIsFloat($result);
    }

    public function testParseNegativeFloat(): void
    {
        $result = $this->parser->parse('-2.718');

        $this->assertEquals(-2.718, $result);
    }

    public function testParseIntegerAsFloat(): void
    {
        $result = $this->parser->parse('42');

        $this->assertEquals(42.0, $result);
    }

    public function testParseZero(): void
    {
        $result = $this->parser->parse('0');

        $this->assertEquals(0.0, $result);
    }

    public function testParseNegativeZero(): void
    {
        $result = $this->parser->parse('-0');

        $this->assertEquals(-0.0, $result);
    }

    public function testParseWithoutDecimalPoint(): void
    {
        $result = $this->parser->parse('123');

        $this->assertEquals(123.0, $result);
    }

    public function testParseWithTrailingZeros(): void
    {
        $result = $this->parser->parse('1.500');

        $this->assertEquals(1.5, $result);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty FLOAT value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseStringAsFloat(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid FLOAT format');

        $this->parser->parse('abc');
    }

    public function testParseWithParameters(): void
    {
        $result = $this->parser->parse('2.5', ['TYPE' => 'FLOAT']);

        $this->assertEquals(2.5, $result);
    }

    public function testGetType(): void
    {
        $this->assertEquals('FLOAT', $this->parser->getType());
    }

    public function testCanParseValidPositive(): void
    {
        $this->assertTrue($this->parser->canParse('3.14'));
    }

    public function testCanParseValidNegative(): void
    {
        $this->assertTrue($this->parser->canParse('-2.718'));
    }

    public function testCanParseInteger(): void
    {
        $this->assertTrue($this->parser->canParse('42'));
    }

    public function testCanParseZero(): void
    {
        $this->assertTrue($this->parser->canParse('0'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseString(): void
    {
        $this->assertFalse($this->parser->canParse('abc'));
    }
}
