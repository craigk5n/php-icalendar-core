<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\CalAddressParser;
use PHPUnit\Framework\TestCase;

class CalAddressParserTest extends TestCase
{
    private CalAddressParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CalAddressParser();
    }

    public function testParseSimpleMailto(): void
    {
        $result = $this->parser->parse('mailto:test@example.com');

        $this->assertEquals('mailto:test@example.com', $result);
    }

    public function testParseMailtoWithName(): void
    {
        $result = $this->parser->parse('mailto:John Doe <john@example.com>');

        $this->assertEquals('mailto:John Doe <john@example.com>', $result);
    }

    public function testParseMailtoWithSubject(): void
    {
        $result = $this->parser->parse('mailto:test@example.com?subject=Meeting');

        $this->assertEquals('mailto:test@example.com?subject=Meeting', $result);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty CAL-ADDRESS value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseMissingScheme(): void
    {
        $this->parser->setStrict(true);
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('must be a URI with scheme');

        $this->parser->parse('test@example.com');
    }

    public function testParseNonMailtoScheme(): void
    {
        $this->parser->setStrict(true);
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('scheme must be mailto');

        $this->parser->parse('https://example.com');
    }

    public function testParseMailtoWithoutAddress(): void
    {
        $this->parser->setStrict(true);
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('missing email address');

        $this->parser->parse('mailto:');
    }

    public function testGetType(): void
    {
        $this->assertEquals('CAL-ADDRESS', $this->parser->getType());
    }

    public function testCanParseValidMailto(): void
    {
        $this->assertTrue($this->parser->canParse('mailto:test@example.com'));
    }

    public function testCanParseMailtoWithName(): void
    {
        $this->assertTrue($this->parser->canParse('mailto:John Doe <john@example.com>'));
    }

    public function testCanParseMailtoWithQuery(): void
    {
        $this->assertTrue($this->parser->canParse('mailto:test@example.com?subject=Test'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseMissingScheme(): void
    {
        $this->assertFalse($this->parser->canParse('test@example.com'));
    }

    public function testCanParseNonMailto(): void
    {
        $this->assertFalse($this->parser->canParse('https://example.com'));
    }

    public function testCanParseMailtoWithoutAddress(): void
    {
        $this->assertFalse($this->parser->canParse('mailto:'));
    }
}
