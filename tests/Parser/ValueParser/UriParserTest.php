<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\UriParser;
use PHPUnit\Framework\TestCase;

class UriParserTest extends TestCase
{
    private UriParser $parser;

    protected function setUp(): void
    {
        $this->parser = new UriParser();
        $this->parser->setStrict(true);
    }

    public function testParseHttpUrl(): void
    {
        $result = $this->parser->parse('https://example.com');

        $this->assertEquals('https://example.com', $result);
    }

    public function testParseHttpUrlWithPath(): void
    {
        $result = $this->parser->parse('https://example.com/calendar/event');

        $this->assertEquals('https://example.com/calendar/event', $result);
    }

    public function testParseHttpUrlWithQuery(): void
    {
        $result = $this->parser->parse('https://example.com?type=event&id=123');

        $this->assertEquals('https://example.com?type=event&id=123', $result);
    }

    public function testParseFtpUrl(): void
    {
        $result = $this->parser->parse('ftp://ftp.example.com/files');

        $this->assertEquals('ftp://ftp.example.com/files', $result);
    }

    public function testParseMailtoUrl(): void
    {
        $result = $this->parser->parse('mailto:test@example.com');

        $this->assertEquals('mailto:test@example.com', $result);
    }

    public function testParseEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty URI value');

        $this->parser->parse('');
    }

    public function testParseWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('   ');
    }

    public function testParseInvalidUrl(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid URI format');

        $this->parser->parse('not a valid url');
    }

    public function testParseMissingScheme(): void
    {
        $this->expectException(ParseException::class);

        $this->parser->parse('example.com');
    }

    public function testGetType(): void
    {
        $this->assertEquals('URI', $this->parser->getType());
    }

    public function testCanParseValidHttpUrl(): void
    {
        $this->assertTrue($this->parser->canParse('https://example.com'));
    }

    public function testCanParseValidFtpUrl(): void
    {
        $this->assertTrue($this->parser->canParse('ftp://ftp.example.com'));
    }

    public function testCanParseValidMailto(): void
    {
        $this->assertTrue($this->parser->canParse('mailto:test@example.com'));
    }

    public function testCanParseEmpty(): void
    {
        $this->assertFalse($this->parser->canParse(''));
    }

    public function testCanParseWhitespace(): void
    {
        $this->assertFalse($this->parser->canParse('   '));
    }

    public function testCanParseInvalidUrl(): void
    {
        $this->assertFalse($this->parser->canParse('not a valid url'));
    }

    public function testCanParseMissingScheme(): void
    {
        $this->assertFalse($this->parser->canParse('example.com'));
    }
}
