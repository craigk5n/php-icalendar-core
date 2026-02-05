<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\TextParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TextParser class
 */
class TextParserTest extends TestCase
{
    private TextParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TextParser();
    }

    public function testUnescapeBackslash(): void
    {
        $result = $this->parser->parse("Meeting at Joe's");

        $this->assertEquals("Meeting at Joe's", $result);
    }

    public function testUnescapeSemicolon(): void
    {
        $result = $this->parser->parse('Team\\; Department A');

        $this->assertEquals('Team; Department A', $result);
    }

    public function testUnescapeComma(): void
    {
        $result = $this->parser->parse('Meeting\\, Lunch\\, Coffee');

        $this->assertEquals('Meeting, Lunch, Coffee', $result);
    }

    public function testUnescapeNewlineLowercase(): void
    {
        $result = $this->parser->parse('Line 1\\nLine 2');

        $this->assertEquals("Line 1\nLine 2", $result);
    }

    public function testUnescapeNewlineUppercase(): void
    {
        $result = $this->parser->parse('Line 1\\NLine 2');

        $this->assertEquals("Line 1\nLine 2", $result);
    }

    public function testParseEmptyText(): void
    {
        $result = $this->parser->parse('');

        $this->assertEquals('', $result);
    }

    public function testParseUnicodeText(): void
    {
        $result = $this->parser->parse('Meeting in Café');

        $this->assertEquals('Meeting in Café', $result);
    }

    public function testParseUnicodeEmoji(): void
    {
        $result = $this->parser->parse('Meeting 😀 in Conference Room');

        $this->assertEquals('Meeting 😀 in Conference Room', $result);
    }

    public function testParseUnicodeCjk(): void
    {
        $result = $this->parser->parse('Meeting in 東京');

        $this->assertEquals('Meeting in 東京', $result);
    }

    public function testParseUnicodeArabic(): void
    {
        $result = $this->parser->parse('Meeting in القاهرة');

        $this->assertEquals('Meeting in القاهرة', $result);
    }

    public function testParseVeryLongText(): void
    {
        $longText = str_repeat('This is a very long text with some escaped characters like \\, \\, and \\; and \\n.', 100);
        $result = $this->parser->parse($longText);

        $this->assertIsString($result);
        $this->assertGreaterThan(1000, strlen($result));
    }

    public function testParsePlainText(): void
    {
        $result = $this->parser->parse('Simple plain text without escapes');

        $this->assertEquals('Simple plain text without escapes', $result);
    }

    public function testParseMultipleEscapes(): void
    {
        $result = $this->parser->parse('Meeting\\, Lunch\\; Dinner\\nTomorrow');

        $this->assertEquals("Meeting, Lunch; Dinner\nTomorrow", $result);
    }

    public function testParseConsecutiveBackslashes(): void
    {
        $result = $this->parser->parse('Path: C:\\\\Windows\\\\System32');

        $this->assertEquals('Path: C:\\Windows\\System32', $result);
    }

    public function testParseInvalidEscapeSequence(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid escape sequence");

        $this->parser->parse('Text with \\x invalid escape');
    }

    public function testParseTrailingBackslash(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Incomplete escape sequence");

        $this->parser->parse('Text with trailing backslash\\');
    }

    public function testParseRealWorldDescription(): void
    {
        $text = 'Weekly team meeting\\nAgenda:\\n1. Project status\\n2. Budget review\\n3. Q&A';
        $result = $this->parser->parse($text);

        $expected = "Weekly team meeting\nAgenda:\n1. Project status\n2. Budget review\n3. Q&A";
        $this->assertEquals($expected, $result);
    }

    public function testGetType(): void
    {
        $this->assertEquals('TEXT', $this->parser->getType());
    }

    public function testCanParseValid(): void
    {
        $this->assertTrue($this->parser->canParse('Simple text'));
        $this->assertTrue($this->parser->canParse('Text with \\; escapes'));
        $this->assertTrue($this->parser->canParse('Text with \\\\ double backslash'));
    }

    public function testCanParseInvalidTrailingBackslash(): void
    {
        // A single backslash at the end is invalid
        $this->assertFalse($this->parser->canParse('Text ending with backslash\\'));
    }

    public function testCanParseValidDoubleBackslashAtEnd(): void
    {
        // Double backslash at the end is valid (represents a single backslash)
        $this->assertTrue($this->parser->canParse('Text ending with backslash\\\\'));
    }

    public function testParseSpecialCharactersPreserved(): void
    {
        $result = $this->parser->parse('Price: $100, Room: A-101, Time: 2:00 PM');

        $this->assertEquals('Price: $100, Room: A-101, Time: 2:00 PM', $result);
    }

    public function testParseMixedContent(): void
    {
        $result = $this->parser->parse('Meeting\\, then lunch\\; then back to work\\nAt 1:00 PM');

        $this->assertEquals("Meeting, then lunch; then back to work\nAt 1:00 PM", $result);
    }

    public function testParseOnlyBackslash(): void
    {
        $result = $this->parser->parse('\\\\');

        $this->assertEquals('\\', $result);
    }

    public function testParseOnlyNewlineEscape(): void
    {
        $result = $this->parser->parse('\\n');

        $this->assertEquals("\n", $result);
    }

    public function testParseEmptyEscape(): void
    {
        // A single backslash at the end is incomplete
        $this->expectException(ParseException::class);

        $this->parser->parse('text\\');
    }

    public function testParseWithParameters(): void
    {
        // TEXT parser ignores parameters
        $result = $this->parser->parse('Simple text', ['LANGUAGE' => 'en-US']);

        $this->assertEquals('Simple text', $result);
    }

    public function testParseMultilineEscaped(): void
    {
        $text = "Line 1\\nLine 2\\nLine 3";
        $result = $this->parser->parse($text);

        $this->assertEquals("Line 1\nLine 2\nLine 3", $result);
    }
}
