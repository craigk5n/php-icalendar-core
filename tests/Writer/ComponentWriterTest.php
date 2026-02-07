<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VTodo;
use Icalendar\Component\VJournal;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VTimezone;
use Icalendar\Component\Standard;
use Icalendar\Component\VAlarm;
use Icalendar\Property\GenericProperty;
use Icalendar\Value\TextValue;
use Icalendar\Writer\ComponentWriter;
use Icalendar\Writer\PropertyWriter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ComponentWriter
 */
class ComponentWriterTest extends TestCase
{
    private ComponentWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new ComponentWriter();
    }

    /**
     * Test writing a simple VCALENDAR component
     */
    public function testWriteVCalendar(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//MyApp//Test//EN');
        $calendar->setVersion('2.0');

        $result = $this->writer->write($calendar);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $result);
        $this->assertStringContainsString('END:VCALENDAR', $result);
        $this->assertStringContainsString('PRODID:-//MyApp//Test//EN', $result);
        $this->assertStringContainsString('VERSION:2.0', $result);
    }

    /**
     * Test writing a VEVENT component
     */
    public function testWriteVEvent(): void
    {
        $event = new VEvent();
        $event->setUid('test-uid@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Test Event');
        $event->setDescription('This is a test event');

        $result = $this->writer->write($event);

        $this->assertStringContainsString('BEGIN:VEVENT', $result);
        $this->assertStringContainsString('END:VEVENT', $result);
        $this->assertStringContainsString('UID:test-uid@example.com', $result);
        $this->assertStringContainsString('DTSTAMP:20260215T100000Z', $result);
        $this->assertStringContainsString('SUMMARY:Test Event', $result);
    }

    /**
     * Test writing a VEVENT with parameters
     */
    public function testWriteVEventWithParameters(): void
    {
        $event = new VEvent();
        $event->setUid('test-uid@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Team Meeting');

        $property = GenericProperty::create('ATTENDEE', 'mailto:john@example.com');
        $property->setParameter('ROLE', 'REQ-PARTICIPANT');
        $property->setParameter('CN', 'John Doe');
        $event->addProperty($property);

        $result = $this->writer->write($event);

        $this->assertStringContainsString('ATTENDEE;ROLE=REQ-PARTICIPANT;CN="John Doe":mailto:john@example.com', $result);
    }

    /**
     * Test writing a VTODO component
     */
    public function testWriteVTodo(): void
    {
        $todo = new VTodo();
        $todo->setUid('todo-uid@example.com');
        $todo->setDtStamp('20260215T100000Z');
        $todo->setSummary('Complete project');
        $todo->setDue('20260228T235959Z');

        $result = $this->writer->write($todo);

        $this->assertStringContainsString('BEGIN:VTODO', $result);
        $this->assertStringContainsString('END:VTODO', $result);
        $this->assertStringContainsString('SUMMARY:Complete project', $result);
    }

    /**
     * Test writing a VJOURNAL component
     */
    public function testWriteVJournal(): void
    {
        $journal = new VJournal();
        $journal->setUid('journal-uid@example.com');
        $journal->setDtStamp('20260215T100000Z');
        $journal->setSummary('Daily notes');

        $result = $this->writer->write($journal);

        $this->assertStringContainsString('BEGIN:VJOURNAL', $result);
        $this->assertStringContainsString('END:VJOURNAL', $result);
        $this->assertStringContainsString('SUMMARY:Daily notes', $result);
    }

    /**
     * Test writing a VFREEBUSY component
     */
    public function testWriteVFreeBusy(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setUid('freebusy-uid@example.com');
        $freebusy->setDtStamp('20260215T100000Z');
        $freebusy->setDtStart('20260215T000000Z');
        $freebusy->setDtEnd('20260228T000000Z');

        $result = $this->writer->write($freebusy);

        $this->assertStringContainsString('BEGIN:VFREEBUSY', $result);
        $this->assertStringContainsString('END:VFREEBUSY', $result);
    }

    /**
     * Test writing a VTIMEZONE component
     */
    public function testWriteVTimezone(): void
    {
        $timezone = new VTimezone();
        $timezone->setTzid('America/New_York');

        $standard = new Standard();
        $standard->setDtStart(new \DateTimeImmutable('2025-11-02 02:00:00'));
        $standard->setTzoffsetfrom(-14400);  // -4 hours in seconds
        $standard->setTzoffsetto(-18000);   // -5 hours in seconds
        $standard->setTzname('EST');
        $timezone->addComponent($standard);

        $result = $this->writer->write($timezone);

        $this->assertStringContainsString('BEGIN:VTIMEZONE', $result);
        $this->assertStringContainsString('END:VTIMEZONE', $result);
        $this->assertStringContainsString('TZID:America/New_York', $result);
        $this->assertStringContainsString('BEGIN:STANDARD', $result);
        $this->assertStringContainsString('END:STANDARD', $result);
    }

    /**
     * Test writing a VALARM component
     */
    public function testWriteVAlarm(): void
    {
        $alarm = new VAlarm();
        $alarm->setAction('DISPLAY');
        $alarm->setTrigger('-PT15M');
        $alarm->setDescription('Reminder');

        $result = $this->writer->write($alarm);

        $this->assertStringContainsString('BEGIN:VALARM', $result);
        $this->assertStringContainsString('END:VALARM', $result);
        $this->assertStringContainsString('ACTION:DISPLAY', $result);
        $this->assertStringContainsString('TRIGGER:-PT15M', $result);
    }

    /**
     * Test writing component with sub-components
     */
    public function testWriteComponentWithSubComponents(): void
    {
        $event = new VEvent();
        $event->setUid('test-uid@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Meeting with alarm');

        $alarm = new VAlarm();
        $alarm->setAction('DISPLAY');
        $alarm->setTrigger('-PT30M');
        $alarm->setDescription('Meeting starts soon');
        $event->addAlarm($alarm);

        $result = $this->writer->write($event);

        $this->assertStringContainsString('BEGIN:VEVENT', $result);
        $this->assertStringContainsString('END:VEVENT', $result);
        $this->assertStringContainsString('BEGIN:VALARM', $result);
        $this->assertStringContainsString('END:VALARM', $result);
        $this->assertStringContainsString('ACTION:DISPLAY', $result);
    }

    /**
     * Test writing empty component (no properties, no sub-components)
     */
    public function testWriteEmptyComponent(): void
    {
        $event = new VEvent();
        $event->setUid('minimal-uid@example.com');
        $event->setDtStamp('20260215T100000Z');

        $result = $this->writer->write($event);

        $this->assertEquals(
            "BEGIN:VEVENT\r\nUID:minimal-uid@example.com\r\nDTSTAMP:20260215T100000Z\r\nEND:VEVENT",
            $result
        );
    }

    /**
     * Test writing multiple components
     */
    public function testWriteMultipleComponents(): void
    {
        $event1 = new VEvent();
        $event1->setUid('event-1@example.com');
        $event1->setDtStamp('20260215T100000Z');
        $event1->setSummary('Event 1');

        $event2 = new VEvent();
        $event2->setUid('event-2@example.com');
        $event2->setDtStamp('20260215T100000Z');
        $event2->setSummary('Event 2');

        $result = $this->writer->writeMultiple([$event1, $event2]);

        $this->assertStringContainsString('SUMMARY:Event 1', $result);
        $this->assertStringContainsString('SUMMARY:Event 2', $result);
        $this->assertStringContainsString('BEGIN:VEVENT', $result);
        $this->assertStringContainsString('END:VEVENT', $result);
    }

    /**
     * Test that property writer can be injected
     */
    public function testPropertyWriterCanBeInjected(): void
    {
        $propertyWriter = new PropertyWriter();
        $writer = new ComponentWriter($propertyWriter);

        $this->assertSame($propertyWriter, $writer->getPropertyWriter());
    }

    /**
     * Test that property writer can be set
     */
    public function testPropertyWriterCanBeSet(): void
    {
        $propertyWriter = new PropertyWriter();
        $this->writer->setPropertyWriter($propertyWriter);

        $this->assertSame($propertyWriter, $this->writer->getPropertyWriter());
    }

    /**
     * Test writing component with special characters in text values
     */
    public function testWriteWithSpecialCharacters(): void
    {
        $event = new VEvent();
        $event->setUid('test@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Meeting; with\\special, chars');
        $event->setDescription("Line 1\nLine 2");

        $result = $this->writer->write($event);

        // RFC 5545 requires escaping of ; , and \ in TEXT values
        $this->assertStringContainsString('SUMMARY:Meeting\\; with\\\\special\\, chars', $result);
        $this->assertStringContainsString("DESCRIPTION:Line 1\\nLine 2", $result);
    }

    /**
     * Test writing component with multiple properties of same type
     */
    public function testWriteMultipleSameProperty(): void
    {
        $event = new VEvent();
        $event->setUid('test@example.com');
        $event->setDtStamp('20260215T100000Z');

        $prop1 = GenericProperty::create('ATTENDEE', 'mailto:a@example.com');
        $event->addProperty($prop1);

        $prop2 = GenericProperty::create('ATTENDEE', 'mailto:b@example.com');
        $event->addProperty($prop2);

        $result = $this->writer->write($event);

        $this->assertStringContainsString('ATTENDEE:mailto:a@example.com', $result);
        $this->assertStringContainsString('ATTENDEE:mailto:b@example.com', $result);
    }

    // --- New tests for STYLED-DESCRIPTION writing ---

    /**
     * Test writing STYLED-DESCRIPTION with HTML content.
     */
    public function testWriteStyledDescriptionHtml(): void
    {
        $event = new VEvent();
        $event->setUid('styled-desc-html-write@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Event with Styled Description');

        // Create STYLED-DESCRIPTION property with HTML content
        $styledDescProp = GenericProperty::create('STYLED-DESCRIPTION', '<html><body><h1>Important</h1><p>Details here.</p></body></html>');
        $styledDescProp->setParameter('VALUE', 'TEXT'); // Explicitly set VALUE type
        $event->addProperty($styledDescProp);

        $result = $this->writer->write($event);

        // Assert that the STYLED-DESCRIPTION property is written correctly.
        // It should contain the raw HTML, with special characters escaped if necessary by TextWriter.
        $this->assertStringContainsString('STYLED-DESCRIPTION;VALUE=TEXT:<html><body><h1>Important</h1><p>Details here.</p></body></html>', $result);
    }

    /**
     * Test writing STYLED-DESCRIPTION with a URI reference.
     */
    public function testWriteStyledDescriptionUri(): void
    {
        $event = new VEvent();
        $event->setUid('styled-desc-uri-write@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Event with Styled Description URI');

        // Create STYLED-DESCRIPTION property with a URI
        $styledDescProp = GenericProperty::create('STYLED-DESCRIPTION', 'http://example.com/event/details');
        $styledDescProp->setParameter('VALUE', 'URI'); // Explicitly set VALUE type
        $event->addProperty($styledDescProp);

        $result = $this->writer->write($event);

        // Assert that the STYLED-DESCRIPTION property is written correctly with the URI.
        $this->assertStringContainsString('STYLED-DESCRIPTION;VALUE=URI:http://example.com/event/details', $result);
    }

    /**
     * Test writing STYLED-DESCRIPTION with a plain DESCRIPTION.
     * Plain DESCRIPTION should be omitted due to conflict resolution logic in ComponentWriter.
     */
    public function testWriteStyledDescriptionWithPlainDescription(): void
    {
        $event = new VEvent();
        $event->setUid('styled-desc-conflict-write@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Conflict Test');

        // Add plain DESCRIPTION first
        $plainDescProp = GenericProperty::create('DESCRIPTION', 'This is a plain description.');
        $event->addProperty($plainDescProp);

        // Add STYLED-DESCRIPTION second
        $styledDescProp = GenericProperty::create('STYLED-DESCRIPTION', '<html>Styled text.</html>');
        $styledDescProp->setParameter('VALUE', 'TEXT');
        $event->addProperty($styledDescProp);

        $result = $this->writer->write($event);

        // Assert STYLED-DESCRIPTION is present
        $this->assertStringContainsString('STYLED-DESCRIPTION;VALUE=TEXT:<html>Styled text.</html>', $result);
        // Assert that the plain DESCRIPTION is omitted due to the conflict resolution in ComponentWriter::write
        $this->assertStringNotContainsString('DESCRIPTION:This is a plain description.', $result);
    }

    /**
     * Test writing STYLED-DESCRIPTION with DESCRIPTION;DERIVED=TRUE.
     * Both should be preserved.
     */
    public function testWriteStyledDescriptionWithDerivedDescription(): void
    {
        $event = new VEvent();
        $event->setUid('styled-desc-derived-write@example.com');
        $event->setDtStamp('20260215T100000Z');
        $event->setSummary('Derived Description Test');

        // Add DESCRIPTION with DERIVED=TRUE
        $derivedDescProp = GenericProperty::create('DESCRIPTION', 'This is a derived plain text description.');
        $derivedDescProp->setParameter('DERIVED', 'TRUE');
        $event->addProperty($derivedDescProp);

        // Add STYLED-DESCRIPTION
        $styledDescProp = GenericProperty::create('STYLED-DESCRIPTION', '<html>Styled text.</html>');
        $styledDescProp->setParameter('VALUE', 'TEXT');
        $event->addProperty($styledDescProp);

        $result = $this->writer->write($event);

        // Assert STYLED-DESCRIPTION is present
        $this->assertStringContainsString('STYLED-DESCRIPTION;VALUE=TEXT:<html>Styled text.</html>', $result);
        // Assert DESCRIPTION with DERIVED=TRUE is also present
        $this->assertStringContainsString('DESCRIPTION;DERIVED=TRUE:This is a derived plain text description.', $result);
    }
}
