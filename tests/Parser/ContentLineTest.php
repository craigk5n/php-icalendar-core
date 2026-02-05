<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ContentLine;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ContentLine class
 */
class ContentLineTest extends TestCase
{
    public function testContentLineParsesSimpleProperty(): void
    {
        $line = ContentLine::parse('SUMMARY:Team Meeting');

        $this->assertEquals('SUMMARY', $line->getName());
        $this->assertEquals('Team Meeting', $line->getValue());
        $this->assertEquals('SUMMARY:Team Meeting', $line->getRawLine());
        $this->assertEmpty($line->getParameters());
        $this->assertFalse($line->hasParameters());
    }

    public function testContentLineParsesWithParameters(): void
    {
        $line = ContentLine::parse('DTSTART;TZID=America/New_York:20260210T100000');

        $this->assertEquals('DTSTART', $line->getName());
        $this->assertEquals('20260210T100000', $line->getValue());
        $this->assertTrue($line->hasParameters());
        $this->assertEquals('America/New_York', $line->getParameter('TZID'));
        $this->assertEquals(['TZID' => 'America/New_York'], $line->getParameters());
    }

    public function testContentLineParsesMultipleParameters(): void
    {
        $line = ContentLine::parse('ATTENDEE;ROLE=REQ-PARTICIPANT;CN=John Doe:mailto:john@example.com');

        $this->assertEquals('ATTENDEE', $line->getName());
        $this->assertEquals('mailto:john@example.com', $line->getValue());
        $this->assertTrue($line->hasParameters());
        $this->assertEquals('REQ-PARTICIPANT', $line->getParameter('ROLE'));
        $this->assertEquals('John Doe', $line->getParameter('CN'));
    }

    public function testContentLineHandlesEmptyParameters(): void
    {
        $line = ContentLine::parse('SUMMARY:Meeting');

        $this->assertEmpty($line->getParameters());
        $this->assertFalse($line->hasParameters());
        $this->assertNull($line->getParameter('NONEXISTENT'));
        $this->assertFalse($line->hasParameter('NONEXISTENT'));
    }

    public function testContentLineToStringSimple(): void
    {
        $line = ContentLine::parse('SUMMARY:Team Meeting');

        $this->assertEquals('SUMMARY:Team Meeting', (string) $line);
    }

    public function testContentLineToStringWithParameters(): void
    {
        $line = ContentLine::parse('DTSTART;TZID=America/New_York:20260210T100000');

        $this->assertStringContainsString('DTSTART', (string) $line);
        $this->assertStringContainsString('TZID=America/New_York', (string) $line);
        $this->assertStringContainsString(':20260210T100000', (string) $line);
    }

    public function testContentLineValidationMissingColon(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid content line format: missing colon separator');

        ContentLine::parse('SUMMARY Team Meeting');
    }

    public function testContentLineValidationEmptyName(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid content line format: empty property name');

        ContentLine::parse(':Team Meeting');
    }

    public function testContentLineStoresRawLine(): void
    {
        $rawLine = 'SUMMARY;LANGUAGE=en:Team Meeting';
        $line = ContentLine::parse($rawLine);

        $this->assertEquals($rawLine, $line->getRawLine());
    }

    public function testContentLineWithXNameProperty(): void
    {
        $line = ContentLine::parse('X-WR-CALNAME:My Calendar');

        $this->assertEquals('X-WR-CALNAME', $line->getName());
        $this->assertEquals('My Calendar', $line->getValue());
    }

    public function testContentLineWithEmptyValue(): void
    {
        $line = ContentLine::parse('SUMMARY:');

        $this->assertEquals('SUMMARY', $line->getName());
        $this->assertEquals('', $line->getValue());
    }

    public function testContentLineWithParameterWithoutValue(): void
    {
        // Some parameters can appear without values (rare but valid per RFC)
        $line = ContentLine::parse('ATTENDEE;RSVP:mailto:test@example.com');

        $this->assertEquals('ATTENDEE', $line->getName());
        $this->assertEquals('', $line->getParameter('RSVP'));
    }

    public function testContentLineWithColonInValue(): void
    {
        // Value containing colons should be handled correctly
        $line = ContentLine::parse('DESCRIPTION:Meeting at 10:00 AM');

        $this->assertEquals('DESCRIPTION', $line->getName());
        $this->assertEquals('Meeting at 10:00 AM', $line->getValue());
    }

    public function testContentLineWithSemicolonInValue(): void
    {
        // Value containing semicolons should be handled correctly
        $line = ContentLine::parse('DESCRIPTION:Team A;Team B;Team C');

        $this->assertEquals('DESCRIPTION', $line->getName());
        $this->assertEquals('Team A;Team B;Team C', $line->getValue());
    }

    public function testContentLineWithCommaInValue(): void
    {
        // Value containing commas should be handled correctly
        $line = ContentLine::parse('CATEGORIES:Meeting,Important,Urgent');

        $this->assertEquals('CATEGORIES', $line->getName());
        $this->assertEquals('Meeting,Important,Urgent', $line->getValue());
    }

    public function testContentLineComplexRealWorld(): void
    {
        // Real-world complex line
        $rawLine = 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=John Doe:mailto:john@example.com';
        $line = ContentLine::parse($rawLine);

        $this->assertEquals('ATTENDEE', $line->getName());
        $this->assertEquals('mailto:john@example.com', $line->getValue());
        $this->assertEquals('INDIVIDUAL', $line->getParameter('CUTYPE'));
        $this->assertEquals('REQ-PARTICIPANT', $line->getParameter('ROLE'));
        $this->assertEquals('NEEDS-ACTION', $line->getParameter('PARTSTAT'));
        $this->assertEquals('TRUE', $line->getParameter('RSVP'));
        $this->assertEquals('John Doe', $line->getParameter('CN'));
    }

    public function testContentLineParseExceptionHasContext(): void
    {
        try {
            ContentLine::parse('Invalid line without colon');
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            $this->assertEquals(ParseException::ERR_INVALID_PROPERTY_FORMAT, $e->getErrorCode());
            $this->assertEquals(0, $e->getContentLineNumber());
            $this->assertEquals('Invalid line without colon', $e->getContentLine());
        }
    }

    public function testContentLineConstructorDirectly(): void
    {
        $line = new ContentLine(
            'DTSTART;TZID=UTC:20260210T100000',
            'DTSTART',
            ['TZID' => 'UTC'],
            '20260210T100000'
        );

        $this->assertEquals('DTSTART', $line->getName());
        $this->assertEquals('20260210T100000', $line->getValue());
        $this->assertEquals(['TZID' => 'UTC'], $line->getParameters());
        $this->assertEquals('DTSTART;TZID=UTC:20260210T100000', $line->getRawLine());
    }
}
