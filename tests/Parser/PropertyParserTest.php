<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ContentLine;
use Icalendar\Parser\PropertyParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PropertyParser class
 */
class PropertyParserTest extends TestCase
{
    private PropertyParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new PropertyParser();
    }

    public function testParseSimpleProperty(): void
    {
        $line = $this->parser->parse('SUMMARY:Team Meeting');

        $this->assertInstanceOf(ContentLine::class, $line);
        $this->assertEquals('SUMMARY', $line->getName());
        $this->assertEquals('Team Meeting', $line->getValue());
        $this->assertEmpty($line->getParameters());
        $this->assertFalse($line->hasParameters());
    }

    public function testParsePropertyWithParameters(): void
    {
        $line = $this->parser->parse('DTSTART;TZID=America/New_York:20260210T100000');

        $this->assertEquals('DTSTART', $line->getName());
        $this->assertEquals('20260210T100000', $line->getValue());
        $this->assertTrue($line->hasParameters());
        $this->assertEquals('America/New_York', $line->getParameter('TZID'));
    }

    public function testParseXNameProperty(): void
    {
        $line = $this->parser->parse('X-WR-CALNAME:My Calendar');

        $this->assertEquals('X-WR-CALNAME', $line->getName());
        $this->assertEquals('My Calendar', $line->getValue());
    }

    public function testParsePropertyWithoutParameters(): void
    {
        $line = $this->parser->parse('VERSION:2.0');

        $this->assertEquals('VERSION', $line->getName());
        $this->assertEquals('2.0', $line->getValue());
        $this->assertEmpty($line->getParameters());
    }

    public function testParseInvalidPropertyFormat(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid property format: missing colon separator');

        $this->parser->parse('SUMMARY Team Meeting');
    }

    public function testParseMultipleParameters(): void
    {
        $line = $this->parser->parse('ATTENDEE;ROLE=REQ-PARTICIPANT;CN=John Doe:mailto:john@example.com');

        $this->assertEquals('ATTENDEE', $line->getName());
        $this->assertEquals('mailto:john@example.com', $line->getValue());
        $this->assertEquals('REQ-PARTICIPANT', $line->getParameter('ROLE'));
        $this->assertEquals('John Doe', $line->getParameter('CN'));
    }

    public function testParseManyParameters(): void
    {
        $rawLine = 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=John Doe;X-NUM-GUESTS=0:mailto:john@example.com';
        $line = $this->parser->parse($rawLine);

        $this->assertEquals('ATTENDEE', $line->getName());
        $this->assertEquals('mailto:john@example.com', $line->getValue());
        $this->assertEquals('INDIVIDUAL', $line->getParameter('CUTYPE'));
        $this->assertEquals('REQ-PARTICIPANT', $line->getParameter('ROLE'));
        $this->assertEquals('NEEDS-ACTION', $line->getParameter('PARTSTAT'));
        $this->assertEquals('TRUE', $line->getParameter('RSVP'));
        $this->assertEquals('John Doe', $line->getParameter('CN'));
        $this->assertEquals('0', $line->getParameter('X-NUM-GUESTS'));
    }

    public function testParseQuotedParameterValue(): void
    {
        $line = $this->parser->parse('SUMMARY;LANGUAGE="en-US":Meeting');

        $this->assertEquals('en-US', $line->getParameter('LANGUAGE'));
    }

    public function testParseQuotedParameterWithSpecialChars(): void
    {
        $line = $this->parser->parse('ATTENDEE;CN="Doe, John":mailto:john@example.com');

        $this->assertEquals('Doe, John', $line->getParameter('CN'));
    }

    public function testParseQuotedParameterWithSemicolon(): void
    {
        $line = $this->parser->parse('ATTENDEE;CN="John;Doe":mailto:john@example.com');

        $this->assertEquals('John;Doe', $line->getParameter('CN'));
    }

    public function testParseQuotedParameterWithColon(): void
    {
        $line = $this->parser->parse('SUMMARY;X-CUSTOM="Time: 10:00":Meeting');

        $this->assertEquals('Time: 10:00', $line->getParameter('X-CUSTOM'));
    }

    public function testParseRfc6868NewlineEncoding(): void
    {
        $line = $this->parser->parse('ATTENDEE;CN="John^nDoe":mailto:john@example.com');

        $this->assertEquals("John\nDoe", $line->getParameter('CN'));
    }

    public function testParseRfc6868CaretEncoding(): void
    {
        $line = $this->parser->parse('SUMMARY;X-INFO="Price: ^^50":Meeting');

        $this->assertEquals('Price: ^50', $line->getParameter('X-INFO'));
    }

    public function testParseRfc6868QuoteEncoding(): void
    {
        $line = $this->parser->parse('SUMMARY;X-NOTE="Say ^\'hello^\'":Meeting');

        $this->assertEquals('Say "hello"', $line->getParameter('X-NOTE'));
    }

    public function testParseRfc6868CombinedEncoding(): void
    {
        $line = $this->parser->parse('ATTENDEE;CN="Meeting^nRoom ^\'A^\' ^^50":mailto:room@example.com');

        $this->assertEquals("Meeting\nRoom \"A\" ^50", $line->getParameter('CN'));
    }

    public function testParseInvalidRfc6868Encoding(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid RFC 6868 encoding");

        $this->parser->parse('SUMMARY;X-INVALID="value^z":Test');
    }

    public function testParseMultiValuedParameter(): void
    {
        $line = $this->parser->parse('ATTENDEE;MEMBER="group1@example.com","group2@example.com":mailto:user@example.com');

        $this->assertEquals('group1@example.com,group2@example.com', $line->getParameter('MEMBER'));
    }

    public function testParseParameterWithoutValue(): void
    {
        $line = $this->parser->parse('ATTENDEE;RSVP:mailto:test@example.com');

        $this->assertEquals('', $line->getParameter('RSVP'));
    }

    public function testParseEmptyPropertyName(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid property format: empty property name');

        $this->parser->parse(':Value without name');
    }

    public function testParseInvalidPropertyName(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid property name");

        $this->parser->parse('123INVALID:Value');
    }

    public function testParseInvalidXNameTooShort(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid property name");

        $this->parser->parse('X-:Value');
    }

    public function testParseUnclosedQuotedString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid parameter format: unclosed quoted string');

        $this->parser->parse('ATTENDEE;CN="John Doe:mailto:test@example.com');
    }

    public function testParseMismatchedQuotes(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid parameter format: unclosed quoted string');

        $this->parser->parse('ATTENDEE;CN="John Doe:mailto:test@example.com');
    }

    public function testParseValueWithColon(): void
    {
        $line = $this->parser->parse('DESCRIPTION:Meeting at 10:00 AM');

        $this->assertEquals('Meeting at 10:00 AM', $line->getValue());
    }

    public function testParseValueWithSemicolon(): void
    {
        $line = $this->parser->parse('CATEGORIES:Team A;Team B;Team C');

        $this->assertEquals('Team A;Team B;Team C', $line->getValue());
    }

    public function testParseValueWithComma(): void
    {
        $line = $this->parser->parse('CATEGORIES:Meeting,Important,Urgent');

        $this->assertEquals('Meeting,Important,Urgent', $line->getValue());
    }

    public function testParseComplexRealWorld(): void
    {
        $rawLine = 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=John Doe;X-NUM-GUESTS=0:mailto:john@example.com';
        $line = $this->parser->parse($rawLine);

        $this->assertEquals('ATTENDEE', $line->getName());
        $this->assertEquals('mailto:john@example.com', $line->getValue());
        $this->assertEquals('INDIVIDUAL', $line->getParameter('CUTYPE'));
        $this->assertEquals('REQ-PARTICIPANT', $line->getParameter('ROLE'));
        $this->assertEquals('NEEDS-ACTION', $line->getParameter('PARTSTAT'));
        $this->assertEquals('TRUE', $line->getParameter('RSVP'));
        $this->assertEquals('John Doe', $line->getParameter('CN'));
        $this->assertEquals('0', $line->getParameter('X-NUM-GUESTS'));
    }

    public function testParseWithLineNumber(): void
    {
        try {
            $this->parser->parse('Invalid line without colon', 42);
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            $this->assertEquals(42, $e->getContentLineNumber());
            $this->assertEquals('Invalid line without colon', $e->getContentLine());
        }
    }

    public function testParseXNameWithVendorId(): void
    {
        $line = $this->parser->parse('X-ABC-CUSTOM:Value');

        $this->assertEquals('X-ABC-CUSTOM', $line->getName());
        $this->assertEquals('Value', $line->getValue());
    }

    public function testParseXNameWithLongVendorId(): void
    {
        $line = $this->parser->parse('X-VENDORID-PROPERTY:Value');

        $this->assertEquals('X-VENDORID-PROPERTY', $line->getName());
        $this->assertEquals('Value', $line->getValue());
    }

    public function testParseLowercaseXName(): void
    {
        // Property names are case-insensitive per RFC 5545 §1.3, normalized to uppercase
        $line = $this->parser->parse('x-wr-calname:My Calendar');

        $this->assertEquals('X-WR-CALNAME', $line->getName());
    }

    public function testParseEmptyValue(): void
    {
        $line = $this->parser->parse('SUMMARY:');

        $this->assertEquals('SUMMARY', $line->getName());
        $this->assertEquals('', $line->getValue());
    }

    public function testParseParameterWithEmptyValue(): void
    {
        $line = $this->parser->parse('ATTENDEE;CN=:mailto:test@example.com');

        $this->assertEquals('', $line->getParameter('CN'));
    }

    public function testParseQuotedEmptyValue(): void
    {
        $line = $this->parser->parse('ATTENDEE;CN="":mailto:test@example.com');

        $this->assertEquals('', $line->getParameter('CN'));
    }

    public function testParseLineWithColonInQuotedParamAndValue(): void
    {
        $line = $this->parser->parse('SUMMARY;X-TIME="Start: 10:00":Meeting at 2:00 PM');

        $this->assertEquals('SUMMARY', $line->getName());
        $this->assertEquals('Start: 10:00', $line->getParameter('X-TIME'));
        $this->assertEquals('Meeting at 2:00 PM', $line->getValue());
    }

    public function testParsePreservesRawLine(): void
    {
        $rawLine = 'SUMMARY;LANGUAGE=en:Team Meeting';
        $line = $this->parser->parse($rawLine);

        $this->assertEquals($rawLine, $line->getRawLine());
    }
}
