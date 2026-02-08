<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer;

use Icalendar\Parser\ContentLine;
use Icalendar\Property\GenericProperty;
use Icalendar\Value\TextValue;
use Icalendar\Value\DateTimeValue;
use Icalendar\Writer\PropertyWriter;
use Icalendar\Writer\ValueWriter\ValueWriterFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PropertyWriter
 */
class PropertyWriterTest extends TestCase
{
    private PropertyWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new PropertyWriter();
    }

    /**
     * Test writing a simple property without parameters
     */
    public function testWriteSimpleProperty(): void
    {
        $property = new GenericProperty('SUMMARY', new TextValue('Team Meeting'));

        $result = $this->writer->write($property);

        $this->assertEquals('SUMMARY:Team Meeting', $result);
    }

    /**
     * Test writing a property with parameters
     */
    public function testWritePropertyWithParameters(): void
    {
        $property = new GenericProperty('DTSTART', new DateTimeValue(new \DateTimeImmutable('2026-02-15 10:00:00', new \DateTimeZone('America/New_York'))));
        $property->setParameter('TZID', 'America/New_York');

        $result = $this->writer->write($property);

        $this->assertEquals('DTSTART;TZID=America/New_York:20260215T100000', $result);
    }

    /**
     * Test writing a property with quoted parameter value
     */
    public function testWritePropertyWithQuotedParameter(): void
    {
        $property = new GenericProperty('ATTENDEE', new TextValue('mailto:john@example.com'));
        $property->setParameter('ROLE', 'REQ-PARTICIPANT');
        $property->setParameter('CN', 'John Doe');

        $result = $this->writer->write($property);

        // CN contains space, but according to RFC 5545 §3.2, only : ; , require quoting
        // However, many implementations also quote values with spaces
        // Let's check what we actually produce
        $this->assertStringStartsWith('ATTENDEE;', $result);
        $this->assertStringContainsString('ROLE=REQ-PARTICIPANT', $result);
        $this->assertStringContainsString('CN="John Doe"', $result);
        $this->assertStringEndsWith(':mailto:john@example.com', $result);
    }

    /**
     * Test writing a property with parameter containing characters requiring quoting
     */
    public function testWritePropertyWithColonInParameter(): void
    {
        $property = new GenericProperty('X-TEST', new TextValue('value'));
        $property->setParameter('VALUE', 'http://example.com');

        $result = $this->writer->write($property);

        // The : in the parameter value requires quoting
        $this->assertEquals('X-TEST;VALUE="http://example.com":value', $result);
    }

    /**
     * Test writing a property with parameter containing semicolon
     */
    public function testWritePropertyWithSemicolonInParameter(): void
    {
        $property = new GenericProperty('X-TEST', new TextValue('value'));
        $property->setParameter('VALUE', 'a;b');

        $result = $this->writer->write($property);

        // The ; in the parameter value requires quoting
        $this->assertEquals('X-TEST;VALUE="a;b":value', $result);
    }

    /**
     * Test writing a property with parameter containing comma
     */
    public function testWritePropertyWithCommaInParameter(): void
    {
        $property = new GenericProperty('X-TEST', new TextValue('value'));
        $property->setParameter('VALUE', 'a,b');

        $result = $this->writer->write($property);

        // The , in the parameter value requires quoting
        $this->assertEquals('X-TEST;VALUE="a,b":value', $result);
    }

    /**
     * Test writing a property with RFC 6868 encoding in parameter
     */
    public function testWritePropertyWithRfc6868(): void
    {
        $property = new GenericProperty('X-TEST', new TextValue('value'));
        $property->setParameter('VALUE', "Test\nNewline");

        $result = $this->writer->write($property);

        // Newline should be encoded as ^n and value should be quoted
        $this->assertEquals('X-TEST;VALUE="Test^nNewline":value', $result);
    }

    /**
     * Test writing a property with double quote in parameter (RFC 6868)
     */
    public function testWritePropertyWithQuoteInParameter(): void
    {
        $property = new GenericProperty('X-TEST', new TextValue('value'));
        $property->setParameter('VALUE', 'Say "Hello"');

        $result = $this->writer->write($property);

        // Double quote should be encoded as ^' and value should be quoted
        $this->assertEquals("X-TEST;VALUE=\"Say ^'Hello^'\":value", $result);
    }

    /**
     * Test writing a property with caret in parameter (RFC 6868)
     */
    public function testWritePropertyWithCaretInParameter(): void
    {
        $property = new GenericProperty('X-TEST', new TextValue('value'));
        $property->setParameter('VALUE', 'a^b');

        $result = $this->writer->write($property);

        // Caret should be encoded as ^^
        $this->assertEquals('X-TEST;VALUE="a^^b":value', $result);
    }

    /**
     * Test writing a property with multi-valued parameter
     */
    public function testWritePropertyWithMultiValue(): void
    {
        $property = new GenericProperty('X-TEST', new TextValue('value'));
        $property->setParameter('VALUE', 'value1,value2,value3');

        $result = $this->writer->write($property);

        // Multi-valued parameters should preserve the comma (it's the separator)
        // But the whole value should be quoted since it contains comma
        $this->assertEquals('X-TEST;VALUE="value1,value2,value3":value', $result);
    }

    /**
     * Test writing a property without parameters
     */
    public function testWritePropertyWithoutParameters(): void
    {
        $property = new GenericProperty('DESCRIPTION', new TextValue('This is a description'));

        $result = $this->writer->write($property);

        $this->assertEquals('DESCRIPTION:This is a description', $result);
    }

    /**
     * Test writing a ContentLine
     */
    public function testWriteContentLine(): void
    {
        $contentLine = new ContentLine(
            'DTSTART;TZID=America/New_York:20260215T100000',
            'DTSTART',
            ['TZID' => 'America/New_York'],
            '20260215T100000',
            1
        );

        $result = $this->writer->write($contentLine);

        $this->assertEquals('DTSTART;TZID=America/New_York:20260215T100000', $result);
    }

    /**
     * Test writing from array format
     */
    public function testWriteFromArray(): void
    {
        $property = [
            'name' => 'DTSTART',
            'parameters' => ['TZID' => 'America/New_York'],
            'value' => '20260215T100000',
        ];

        $result = $this->writer->write($property);

        $this->assertEquals('DTSTART;TZID=America/New_York:20260215T100000', $result);
    }

    /**
     * Test writing from array with type specification
     */
    public function testWriteFromArrayWithType(): void
    {
        $property = [
            'name' => 'DTSTART',
            'parameters' => ['TZID' => 'UTC'],
            'value' => new \DateTimeImmutable('2026-02-15 10:00:00', new \DateTimeZone('UTC')),
            'type' => 'DATE-TIME',
        ];

        $result = $this->writer->write($property);

        $this->assertEquals('DTSTART;TZID=UTC:20260215T100000Z', $result);
    }

    /**
     * Test writing property with multiple parameters
     */
    public function testWritePropertyWithMultipleParameters(): void
    {
        $property = new GenericProperty('ATTENDEE', new TextValue('mailto:attendee@example.com'));
        $property->setParameter('ROLE', 'REQ-PARTICIPANT');
        $property->setParameter('PARTSTAT', 'ACCEPTED');
        $property->setParameter('RSVP', 'TRUE');

        $result = $this->writer->write($property);

        $this->assertStringStartsWith('ATTENDEE;', $result);
        $this->assertStringContainsString('ROLE=REQ-PARTICIPANT', $result);
        $this->assertStringContainsString('PARTSTAT=ACCEPTED', $result);
        $this->assertStringContainsString('RSVP=TRUE', $result);
        $this->assertStringEndsWith(':mailto:attendee@example.com', $result);
    }

    /**
     * Test that value writer factory can be injected
     */
    public function testValueWriterFactoryCanBeInjected(): void
    {
        $factory = new ValueWriterFactory();
        $writer = new PropertyWriter($factory);

        $this->assertSame($factory, $writer->getValueWriterFactory());
    }

    /**
     * Test that value writer factory can be set
     */
    public function testValueWriterFactoryCanBeSet(): void
    {
        $factory = new ValueWriterFactory();
        $this->writer->setValueWriterFactory($factory);

        $this->assertSame($factory, $this->writer->getValueWriterFactory());
    }

    /**
     * Test complex property with quoted parameter requiring RFC 6868 encoding
     */
    public function testWriteComplexQuotedParameter(): void
    {
        $property = new GenericProperty('ATTENDEE', new TextValue('mailto:test@example.com'));
        // Value contains both a comma (requires quoting) and a quote (requires RFC 6868)
        $property->setParameter('CN', 'Doe, John "Johnny"');

        $result = $this->writer->write($property);

        $this->assertStringContainsString("CN=\"Doe, John ^'Johnny^'\"", $result);
    }
}
