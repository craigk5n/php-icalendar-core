<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ParameterParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ParameterParser class
 */
class ParameterParserTest extends TestCase
{
    private ParameterParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ParameterParser();
    }

    public function testParseSimpleParameter(): void
    {
        $params = $this->parser->parse('TZID=America/New_York');

        $this->assertEquals(['TZID' => 'America/New_York'], $params);
    }

    public function testParseMultipleParameters(): void
    {
        $params = $this->parser->parse('ROLE=REQ-PARTICIPANT;CN=John Doe');

        $this->assertEquals([
            'ROLE' => 'REQ-PARTICIPANT',
            'CN' => 'John Doe'
        ], $params);
    }

    public function testParseQuotedParameter(): void
    {
        $params = $this->parser->parse('CN="John Doe"');

        $this->assertEquals(['CN' => 'John Doe'], $params);
    }

    public function testParseQuotedParameterWithComma(): void
    {
        $params = $this->parser->parse('CN="Doe, John"');

        $this->assertEquals(['CN' => 'Doe, John'], $params);
    }

    public function testParseQuotedParameterWithSemicolon(): void
    {
        $params = $this->parser->parse('CN="John;Doe"');

        $this->assertEquals(['CN' => 'John;Doe'], $params);
    }

    public function testParseQuotedParameterWithColon(): void
    {
        $params = $this->parser->parse('X-CUSTOM="Time: 10:00"');

        $this->assertEquals(['X-CUSTOM' => 'Time: 10:00'], $params);
    }

    public function testParseMultiValueParameter(): void
    {
        $params = $this->parser->parse('MEMBER="group1@example.com","group2@example.com"');

        $this->assertEquals(['MEMBER' => 'group1@example.com,group2@example.com'], $params);
    }

    public function testParseRfc6868NewlineDecoding(): void
    {
        $params = $this->parser->parse('CN="John^nDoe"');

        $this->assertEquals(['CN' => "John\nDoe"], $params);
    }

    public function testParseRfc6868CaretDecoding(): void
    {
        $params = $this->parser->parse('X-INFO="Price: ^^50"');

        $this->assertEquals(['X-INFO' => 'Price: ^50'], $params);
    }

    public function testParseRfc6868QuoteDecoding(): void
    {
        $params = $this->parser->parse('X-NOTE="Say ^\'hello^\'"');

        $this->assertEquals(['X-NOTE' => 'Say "hello"'], $params);
    }

    public function testParseRfc6868CombinedDecoding(): void
    {
        $params = $this->parser->parse('CN="Meeting^nRoom ^\'A^\' ^^50"');

        $this->assertEquals(['CN' => "Meeting\nRoom \"A\" ^50"], $params);
    }

    public function testParseInvalidRfc6868Encoding(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid RFC 6868 encoding");

        $this->parser->parse('X-INVALID="value^z"');
    }

    public function testParseUnquotedValue(): void
    {
        $params = $this->parser->parse('TZID=UTC');

        $this->assertEquals(['TZID' => 'UTC'], $params);
    }

    public function testParseParameterWithoutValue(): void
    {
        $params = $this->parser->parse('RSVP');

        $this->assertEquals(['RSVP' => ''], $params);
    }

    public function testParseEmptyParameterString(): void
    {
        $params = $this->parser->parse('');

        $this->assertEquals([], $params);
    }

    public function testParseInvalidParameterFormat(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid parameter format: empty parameter name');

        $this->parser->parse('=value');
    }

    public function testParseInvalidParameterName(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid parameter name");

        $this->parser->parse('123INVALID=value');
    }

    public function testParseParameterNameStartingWithDigit(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid parameter name");

        $this->parser->parse('1PARAM=value');
    }

    public function testParseUnclosedQuotedString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid parameter format: unclosed quoted string');

        $this->parser->parse('CN="John Doe');
    }

    public function testParseMismatchedQuotes(): void
    {
        // A value with only an opening quote (no closing quote) is detected as unclosed
        // This is caught by splitParameters before parseParameterValue
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid parameter format: unclosed quoted string');

        $this->parser->parse('CN="John Doe');
    }

    public function testParseManyParameters(): void
    {
        $paramString = 'CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=John Doe;X-NUM-GUESTS=0';
        $params = $this->parser->parse($paramString);

        $this->assertEquals([
            'CUTYPE' => 'INDIVIDUAL',
            'ROLE' => 'REQ-PARTICIPANT',
            'PARTSTAT' => 'NEEDS-ACTION',
            'RSVP' => 'TRUE',
            'CN' => 'John Doe',
            'X-NUM-GUESTS' => '0'
        ], $params);
    }

    public function testParseComplexQuotedParameters(): void
    {
        $paramString = 'ROLE=REQ-PARTICIPANT;CN="Doe, John";MEMBER="group1@example.com","group2@example.com"';
        $params = $this->parser->parse($paramString);

        $this->assertEquals([
            'ROLE' => 'REQ-PARTICIPANT',
            'CN' => 'Doe, John',
            'MEMBER' => 'group1@example.com,group2@example.com'
        ], $params);
    }

    public function testParseQuotedParameterWithSemicolonInValue(): void
    {
        // A semicolon inside quoted value should not be treated as parameter separator
        $paramString = 'CN="John;Doe";ROLE=REQ-PARTICIPANT';
        $params = $this->parser->parse($paramString);

        $this->assertEquals([
            'CN' => 'John;Doe',
            'ROLE' => 'REQ-PARTICIPANT'
        ], $params);
    }

    public function testParseWithLineNumberAndRawLine(): void
    {
        try {
            $this->parser->parse('=invalid', 42, 'ATTENDEE;=invalid:mailto:test@example.com');
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            $this->assertEquals(42, $e->getContentLineNumber());
            $this->assertEquals('ATTENDEE;=invalid:mailto:test@example.com', $e->getContentLine());
        }
    }

    public function testParseEmptyValue(): void
    {
        $params = $this->parser->parse('CN=');

        $this->assertEquals(['CN' => ''], $params);
    }

    public function testParseQuotedEmptyValue(): void
    {
        $params = $this->parser->parse('CN=""');

        $this->assertEquals(['CN' => ''], $params);
    }

    public function testParseMixedQuotedAndUnquotedValues(): void
    {
        $paramString = 'TZID=America/New_York;CN="John Doe";LANGUAGE=en';
        $params = $this->parser->parse($paramString);

        $this->assertEquals([
            'TZID' => 'America/New_York',
            'CN' => 'John Doe',
            'LANGUAGE' => 'en'
        ], $params);
    }

    public function testParseParameterWithHyphen(): void
    {
        $params = $this->parser->parse('X-CUSTOM-VALUE=test');

        $this->assertEquals(['X-CUSTOM-VALUE' => 'test'], $params);
    }

    public function testParseXNameParameter(): void
    {
        $params = $this->parser->parse('X-WR-CALNAME=My Calendar');

        $this->assertEquals(['X-WR-CALNAME' => 'My Calendar'], $params);
    }

    public function testParseMultiValueWithMixedQuotes(): void
    {
        // Mix of quoted and unquoted values in multi-value parameter
        $params = $this->parser->parse('ATTENDEE="quoted@example.com",unquoted@example.com,"another quoted@example.com"');

        $this->assertEquals(['ATTENDEE' => 'quoted@example.com,unquoted@example.com,another quoted@example.com'], $params);
    }

    public function testParseParameterWithSpecialCharsInUnquotedValue(): void
    {
        // Unquoted values should be preserved as-is
        $params = $this->parser->parse('X-VALUE=some@value.com');

        $this->assertEquals(['X-VALUE' => 'some@value.com'], $params);
    }
}
