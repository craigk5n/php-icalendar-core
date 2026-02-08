<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\BooleanParser;
use PHPUnit\Framework\TestCase;

class BooleanParserTest extends TestCase
{
    private BooleanParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new BooleanParser();
    }

    public function testParseTrueLowercase(): void
    {
        $result = $this->parser->parse('TRUE');

        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    public function testParseTrueUppercase(): void
    {
        $result = $this->parser->parse('true');

        $this->assertTrue($result);
    }

    public function testParseTrueMixedCase(): void
    {
        $result = $this->parser->parse('True');

        $this->assertTrue($result);
    }

    public function testParseFalseLowercase(): void
    {
        $result = $this->parser->parse('FALSE');

        $this->assertFalse($result);
    }

    public function testParseFalseUppercase(): void
    {
        $result = $this->parser->parse('false');

        $this->assertFalse($result);
    }

    public function testParseFalseMixedCase(): void
    {
        $result = $this->parser->parse('False');

        $this->assertFalse($result);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty BOOLEAN value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseYes(): void
    {
        $this->parser->setStrict(true);
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('must be TRUE or FALSE');

        $this->parser->parse('yes');
    }

    public function testParseNo(): void
    {
        $this->parser->setStrict(true);
        $this->expectException(ParseException::class);

        $this->parser->parse('no');
    }

    public function testParseOne(): void
    {
        $this->parser->setStrict(true);
        $this->expectException(ParseException::class);

        $this->parser->parse('1');
    }

    public function testParseZero(): void
    {
        $this->parser->setStrict(true);
        $this->expectException(ParseException::class);

        $this->parser->parse('0');
    }

    public function testParseWithParameters(): void
    {
        $result = $this->parser->parse('TRUE', ['TYPE' => 'BOOLEAN']);

        $this->assertTrue($result);
    }

    public function testGetType(): void
    {
        $this->assertEquals('BOOLEAN', $this->parser->getType());
    }

    public function testCanParseTrueLowercase(): void
    {
        $this->assertTrue($this->parser->canParse('TRUE'));
    }

    public function testCanParseTrueUppercase(): void
    {
        $this->assertTrue($this->parser->canParse('true'));
    }

    public function testCanParseFalseLowercase(): void
    {
        $this->assertTrue($this->parser->canParse('FALSE'));
    }

    public function testCanParseFalseUppercase(): void
    {
        $this->assertTrue($this->parser->canParse('false'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseYes(): void
    {
        $this->assertFalse($this->parser->canParse('yes'));
    }

    public function testCanParseOne(): void
    {
        $this->assertFalse($this->parser->canParse('1'));
    }
}
