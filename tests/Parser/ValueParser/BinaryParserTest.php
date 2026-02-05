<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\BinaryParser;
use PHPUnit\Framework\TestCase;

class BinaryParserTest extends TestCase
{
    private BinaryParser $parser;

    protected function setUp(): void
    {
        $this->parser = new BinaryParser();
    }

    public function testParseSimpleBase64(): void
    {
        $encoded = base64_encode('Hello World');
        $result = $this->parser->parse($encoded);

        $this->assertEquals('Hello World', $result);
    }

    public function testParseBase64WithLineWrapping(): void
    {
        $longString = str_repeat('A', 50);
        $encoded = base64_encode($longString);

        $wrapped = substr($encoded, 0, 76) . "\r\n " . substr($encoded, 76);
        $result = $this->parser->parse($wrapped);

        $this->assertEquals($longString, $result);
    }

    public function testParseBase64WithMultipleLines(): void
    {
        $longString = str_repeat('B', 100);
        $encoded = base64_encode($longString);

        $wrapped = '';
        for ($i = 0; $i < strlen($encoded); $i += 76) {
            if ($i > 0) {
                $wrapped .= "\r\n ";
            }
            $wrapped .= substr($encoded, $i, 76);
        }

        $result = $this->parser->parse($wrapped);

        $this->assertEquals($longString, $result);
    }

    public function testParseBinaryData(): void
    {
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD";
        $encoded = base64_encode($binaryData);
        $result = $this->parser->parse($encoded);

        $this->assertEquals($binaryData, $result);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty BINARY value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseInvalidBase64(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid Base64 encoding');

        $this->parser->parse('not!valid!!!base64!!!');
    }

    public function testParseIncompleteBase64(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid Base64 encoding');

        $this->parser->parse('SGVsbG8=d');
    }

    public function testGetType(): void
    {
        $this->assertEquals('BINARY', $this->parser->getType());
    }

    public function testCanParseValidBase64(): void
    {
        $encoded = base64_encode('test');
        $this->assertTrue($this->parser->canParse($encoded));
    }

    public function testCanParseWrappedBase64(): void
    {
        $longString = str_repeat('A', 96);
        $encoded = base64_encode($longString);

        $wrapped = substr($encoded, 0, 76) . "\r\n " . substr($encoded, 76);
        $this->assertTrue($this->parser->canParse($wrapped));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseInvalidBase64(): void
    {
        $this->assertFalse($this->parser->canParse('not!valid!!!base64!!!'));
    }
}
