<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\IntegerParser;
use PHPUnit\Framework\TestCase;

class IntegerParserTest extends TestCase
{
    private IntegerParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IntegerParser();
        $this->parser->setStrict(true);
    }

    public function testParsePositiveInteger(): void
    {
        $result = $this->parser->parse('42');

        $this->assertEquals(42, $result);
        $this->assertIsInt($result);
    }

    public function testParseNegativeInteger(): void
    {
        $result = $this->parser->parse('-123');

        $this->assertEquals(-123, $result);
    }

    public function testParseZero(): void
    {
        $result = $this->parser->parse('0');

        $this->assertEquals(0, $result);
    }

    public function testParseLargeInteger(): void
    {
        $result = $this->parser->parse('2147483647');

        $this->assertEquals(2147483647, $result);
    }

    public function testParseNegativeLargeInteger(): void
    {
        $result = $this->parser->parse('-2147483648');

        $this->assertEquals(-2147483648, $result);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty INTEGER value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseFloatAsInteger(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid INTEGER format');

        $this->parser->parse('3.14');
    }

    public function testParseStringAsInteger(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('abc');
    }

    public function testParseWithLeadingZeros(): void
    {
        $result = $this->parser->parse('007');

        $this->assertEquals(7, $result);
    }

    public function testParseWithParameters(): void
    {
        $result = $this->parser->parse('100', ['TYPE' => 'INTEGER']);

        $this->assertEquals(100, $result);
    }

    public function testGetType(): void
    {
        $this->assertEquals('INTEGER', $this->parser->getType());
    }

    public function testCanParseValidPositive(): void
    {
        $this->assertTrue($this->parser->canParse('42'));
    }

    public function testCanParseValidNegative(): void
    {
        $this->assertTrue($this->parser->canParse('-42'));
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

    public function testCanParseFloat(): void
    {
        $this->assertFalse($this->parser->canParse('3.14'));
    }

    public function testCanParseString(): void
    {
        $this->assertFalse($this->parser->canParse('abc'));
    }
}
