<?php

declare(strict_types=1);

namespace Icalendar\Tests\Fidelity;

use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\TestCase;

class RfcExamplesRoundTripTest extends TestCase
{
    private Parser $parser;
    private Writer $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->writer = new Writer();
    }

    public static function rfcExamplesProvider(): array
    {
        $fixturesDir = __DIR__ . '/../fixtures/rfc5545';
        $files = glob($fixturesDir . '/*.ics');
        
        $data = [];
        foreach ($files as $file) {
            $name = basename($file, '.ics');
            $data[$name] = [$file];
        }
        
        return $data;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rfcExamplesProvider')]
    public function testRoundTripParsesSuccessfully(string $icsPath): void
    {
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs, "Failed to read ICS file: {$icsPath}");

        $calendar = $this->parser->parse($originalIcs);
        $this->assertNotNull($calendar);

        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('BEGIN:VCALENDAR', $roundTripIcs);
        $this->assertStringContainsString('END:VCALENDAR', $roundTripIcs);
        $this->assertStringContainsString('VERSION:2.0', $roundTripIcs);
        $this->assertStringContainsString('PRODID:', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testSimpleEventRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:test@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T100000Z
DURATION:PT1H
SUMMARY:Test Event
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('UID:test@example.com', $roundTripIcs);
        $this->assertStringContainsString('DTSTART:20260102T100000Z', $roundTripIcs);
        $this->assertStringContainsString('SUMMARY:Test Event', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testAllDayEventRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:allday@example.com
DTSTAMP:20260101T000000Z
DTSTART;VALUE=DATE:20260102
SUMMARY:All-day Event
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20260102', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testEventWithAttendeeRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:attendee@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T100000Z
DURATION:PT1H
SUMMARY:Meeting
ATTENDEE;CN="John Doe":mailto:john@example.com
ORGANIZER:mailto:organizer@example.com
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('ATTENDEE', $roundTripIcs);
        $this->assertStringContainsString('ORGANIZER', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testEventWithAlarmRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:alarm@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T100000Z
DURATION:PT1H
SUMMARY:Event with Alarm
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER:-PT15M
DESCRIPTION:Reminder
END:VALARM
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('BEGIN:VALARM', $roundTripIcs);
        $this->assertStringContainsString('ACTION:DISPLAY', $roundTripIcs);
        $this->assertStringContainsString('TRIGGER:-PT15M', $roundTripIcs);
        $this->assertStringContainsString('END:VALARM', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testMultipleEventsRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:event1@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T090000Z
SUMMARY:First Event
END:VEVENT
BEGIN:VEVENT
UID:event2@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T140000Z
SUMMARY:Second Event
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('UID:event1@example.com', $roundTripIcs);
        $this->assertStringContainsString('UID:event2@example.com', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testTodoRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VTODO
UID:todo@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T090000Z
DUE:20260105T170000Z
SUMMARY:Complete project report
PRIORITY:1
STATUS:NEEDS-ACTION
END:VTODO
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('BEGIN:VTODO', $roundTripIcs);
        $this->assertStringContainsString('DUE:20260105T170000Z', $roundTripIcs);
        $this->assertStringContainsString('PRIORITY:1', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testJournalRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VJOURNAL
UID:journal@example.com
DTSTAMP:20260101T000000Z
DTSTART;VALUE=DATE:20260102
SUMMARY:Daily Notes
DESCRIPTION:Journal entry for January 2nd
END:VJOURNAL
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('BEGIN:VJOURNAL', $roundTripIcs);
        $this->assertStringContainsString('SUMMARY:Daily Notes', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testEventWithRruleRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:rrule@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T100000Z
DURATION:PT1H
SUMMARY:Recurring Event
RRULE:FREQ=DAILY;COUNT=5
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('RRULE', $roundTripIcs);
        $this->assertStringContainsString('FREQ=DAILY', $roundTripIcs);
        $this->assertStringContainsString('COUNT=5', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testEventWithExdateRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:exdate@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T100000Z
DURATION:PT1H
SUMMARY:Event with Exception
RRULE:FREQ=DAILY;COUNT=5
EXDATE:20260103T100000Z
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('EXDATE', $roundTripIcs);
        $this->assertStringContainsString('20260103T100000Z', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testEventWithRdateRoundTrip(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:rdate@example.com
DTSTAMP:20260101T000000Z
DTSTART:20260102T100000Z
DURATION:PT1H
SUMMARY:Event with RDATE
RDATE:20260105T100000Z
END:VEVENT
END:VCALENDAR
ICS;

        $calendar = $this->parser->parse($ics);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('RDATE', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }
}
