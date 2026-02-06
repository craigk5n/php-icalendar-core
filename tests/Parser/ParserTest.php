<?php

declare(strict_types=1);

namespace Tests\Parser;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VAlarm;
use Icalendar\Parser\Parser;
use Icalendar\Parser\Lexer;
use Icalendar\Parser\ContentLine;
use Icalendar\Exception\ParseException;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseSimpleEvent(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test-123@example.com\r\nSUMMARY:Test Event\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $this->assertInstanceOf(VCalendar::class, $calendar);
        $this->assertCount(1, $calendar->getComponents('VEVENT'));

        $event = $calendar->getComponents('VEVENT')[0];
        $summaryProp = $event->getProperty('SUMMARY');
        $this->assertNotNull($summaryProp);
        $this->assertEquals('Test Event', $summaryProp->getValue()->getRawValue());
    }

    public function testParseComplexCalendar(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:event-1@example.com\r\nSUMMARY:Meeting\r\nDTSTART:20260210T100000Z\r\nDTEND:20260210T110000Z\r\nDESCRIPTION:Test description\r\nLOCATION:Conference Room\r\nCATEGORIES:Work\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T110000Z\r\nUID:event-2@example.com\r\nSUMMARY:Another Event\r\nDTSTART:20260211T140000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $this->assertCount(2, $calendar->getComponents('VEVENT'));

        $events = $calendar->getComponents('VEVENT');
        $this->assertEquals('Meeting', $events[0]->getProperty('SUMMARY')?->getValue()?->getRawValue());
        $this->assertEquals('Conference Room', $events[0]->getProperty('LOCATION')?->getValue()?->getRawValue());
        $this->assertEquals('Another Event', $events[1]->getProperty('SUMMARY')?->getValue()?->getRawValue());
    }

    public function testParseFile(): void
    {
        $filepath = sys_get_temp_dir() . '/test_calendar.ics';
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:file-test@example.com\r\nSUMMARY:File Test\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        file_put_contents($filepath, $icalData);

        try {
            $calendar = $this->parser->parseFile($filepath);

            $this->assertInstanceOf(VCalendar::class, $calendar);
            $this->assertCount(1, $calendar->getComponents('VEVENT'));

            $event = $calendar->getComponents('VEVENT')[0];
            $this->assertEquals('File Test', $event->getProperty('SUMMARY')?->getValue()?->getRawValue());
        } finally {
            unlink($filepath);
        }
    }

    public function testParseFileNotFound(): void
    {
        try {
            $this->parser->parseFile('/nonexistent/path/calendar.ics');
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            $this->assertEquals('ICAL-IO-001', $e->getErrorCode());
        }
    }

    public function testParseFileXxeBlocked(): void
    {
        $filepath = sys_get_temp_dir() . '/xxe_calendar.ics';
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test\r\n<!ENTITY x SYSTEM \"file:///etc/passwd\">\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test@example.com\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        file_put_contents($filepath, $icalData);

        try {
            $this->parser->parseFile($filepath);
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            $this->assertEquals('ICAL-SEC-005', $e->getErrorCode());
        } finally {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    public function testParseStrictMode(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test@example.com\r\nX-CUSTOM:value\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $this->parser->setStrict(true);
        $calendar = $this->parser->parse($icalData);

        $this->assertInstanceOf(VCalendar::class, $calendar);
        $this->assertCount(1, $calendar->getComponents('VEVENT'));
    }

    public function testParseLenientMode(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test@example.com\r\nSUMMARY:Test\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $this->parser->setStrict(false);
        $calendar = $this->parser->parse($icalData);

        $this->assertInstanceOf(VCalendar::class, $calendar);
        $errors = $this->parser->getErrors();
        $this->assertEmpty($errors);
    }

    public function testParseErrors(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test@example.com\r\nSUMMARY:Test\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);
        $errors = $this->parser->getErrors();

        $this->assertEmpty($errors);
    }

    public function testParseWithVAlarm(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:alarm-test@example.com\r\nSUMMARY:Event with Alarm\r\nBEGIN:VALARM\r\nACTION:DISPLAY\r\nTRIGGER:-PT15M\r\nDESCRIPTION:Reminder\r\nEND:VALARM\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $events = $calendar->getComponents('VEVENT');
        $this->assertCount(1, $events);

        $alarms = $events[0]->getComponents('VALARM');
        $this->assertCount(1, $alarms);

        $alarm = $alarms[0];
        $this->assertEquals('DISPLAY', $alarm->getProperty('ACTION')?->getValue()?->getRawValue());
        $this->assertEquals('Reminder', $alarm->getProperty('DESCRIPTION')?->getValue()?->getRawValue());
    }

    public function testParseWithTimezone(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VTIMEZONE\r\nTZID:America/New_York\r\nBEGIN:STANDARD\r\nDTSTART:20201025T020000\r\nTZOFFSETFROM:-0400\r\nTZOFFSETTO:-0500\r\nTZNAME:EST\r\nEND:STANDARD\r\nBEGIN:DAYLIGHT\r\nDTSTART:20210314T020000\r\nTZOFFSETFROM:-0500\r\nTZOFFSETTO:-0400\r\nTZNAME:EDT\r\nEND:DAYLIGHT\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:tz-test@example.com\r\nSUMMARY:Timezone Event\r\nDTSTART;TZID=America/New_York:20260210T090000\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $this->assertCount(1, $calendar->getComponents('VTIMEZONE'));
        $this->assertCount(1, $calendar->getComponents('VEVENT'));

        $timezone = $calendar->getComponents('VTIMEZONE')[0];
        $this->assertEquals('America/New_York', $timezone->getProperty('TZID')?->getValue()?->getRawValue());

        $standard = $timezone->getComponents('STANDARD');
        $daylight = $timezone->getComponents('DAYLIGHT');
        $this->assertCount(1, $standard);
        $this->assertCount(1, $daylight);
    }

    public function testParseWithVTodo(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VTODO\r\nDTSTAMP:20260206T100000Z\r\nUID:todo-test@example.com\r\nSUMMARY:Buy groceries\r\nDUE:20260210T180000Z\r\nPRIORITY:1\r\nSTATUS:NEEDS-ACTION\r\nEND:VTODO\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $todos = $calendar->getComponents('VTODO');
        $this->assertCount(1, $todos);

        $todo = $todos[0];
        $this->assertEquals('Buy groceries', $todo->getProperty('SUMMARY')?->getValue()?->getRawValue());
        $this->assertEquals('1', $todo->getProperty('PRIORITY')?->getValue()?->getRawValue());
        $this->assertEquals('NEEDS-ACTION', $todo->getProperty('STATUS')?->getValue()?->getRawValue());
    }

    public function testParseWithVJournal(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VJOURNAL\r\nDTSTAMP:20260206T100000Z\r\nUID:journal-test@example.com\r\nSUMMARY:Meeting Notes\r\nDESCRIPTION:Discuss project status\r\nCLASS:PUBLIC\r\nEND:VJOURNAL\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $journals = $calendar->getComponents('VJOURNAL');
        $this->assertCount(1, $journals);

        $journal = $journals[0];
        $this->assertEquals('Meeting Notes', $journal->getProperty('SUMMARY')?->getValue()?->getRawValue());
        $this->assertEquals('PUBLIC', $journal->getProperty('CLASS')?->getValue()?->getRawValue());
    }

    public function testParseWithVFreeBusy(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VFREEBUSY\r\nDTSTAMP:20260206T100000Z\r\nUID:freebusy-test@example.com\r\nDTSTART:20260210T000000Z\r\nDTEND:20260217T000000Z\r\nFREEBUSY:20260210T090000Z/20260210T120000Z,20260211T090000Z/20260211T120000Z\r\nEND:VFREEBUSY\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $freebusies = $calendar->getComponents('VFREEBUSY');
        $this->assertCount(1, $freebusies);

        $fb = $freebusies[0];
        $this->assertNotNull($fb->getProperty('FREEBUSY'));
    }

    public function testParseUnknownComponent(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:UNKNOWNCOMPONENT\r\nTEST:value\r\nEND:UNKNOWNCOMPONENT\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test@example.com\r\nSUMMARY:Valid Event\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $this->parser->setStrict(false);
        $calendar = $this->parser->parse($icalData);

        $this->assertCount(1, $calendar->getComponents('VEVENT'));
    }

    public function testParseWithFoldedLines(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test@example.com\r\nDESCRIPTION:This is a very long description that has been folded onto\r\n multiple lines to test the unfolding functionality of the parser\r\nSUMMARY:Test\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $events = $calendar->getComponents('VEVENT');
        $description = $events[0]->getProperty('DESCRIPTION')?->getValue()?->getRawValue();

        $this->assertStringContainsString('unfolding functionality', $description);
    }

    public function testParseWithLineEndingNormalization(): void
    {
        $icalData = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//Test//Test//EN\nBEGIN:VEVENT\nDTSTAMP:20260206T100000Z\nUID:test@example.com\nSUMMARY:Test\nEND:VEVENT\nEND:VCALENDAR\n";

        $calendar = $this->parser->parse($icalData);

        $events = $calendar->getComponents('VEVENT');
        $this->assertCount(1, $events);
        $this->assertEquals('Test', $events[0]->getProperty('SUMMARY')?->getValue()?->getRawValue());
    }

    public function testParseRoundTrip(): void
    {
        $original = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:roundtrip@example.com\r\nSUMMARY:Round Trip Test\r\nDESCRIPTION:Testing round trip parsing\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($original);
        $this->assertCount(1, $calendar->getComponents('VEVENT'));
    }

    public function testParseWithUtf8Content(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:utf8-test@example.com\r\nSUMMARY:Testing 日本語 and русский текст\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $events = $calendar->getComponents('VEVENT');
        $summary = $events[0]->getProperty('SUMMARY')?->getValue()?->getRawValue();

        $this->assertStringContainsString('日本語', $summary);
        $this->assertStringContainsString('русский', $summary);
    }

    public function testGetErrorsReturnsEmptyArray(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:test@example.com\r\nSUMMARY:Test\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $this->parser->parse($icalData);

        $errors = $this->parser->getErrors();
        $this->assertIsArray($errors);
    }

    public function testSetMaxDepth(): void
    {
        $this->parser->setMaxDepth(50);
        $this->assertEquals(50, $this->parser->getMaxDepth());
    }

    public function testComponentWithParameters(): void
    {
        $icalData = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nDTSTAMP:20260206T100000Z\r\nUID:param-test@example.com\r\nSUMMARY:Test with parameters\r\nDTSTART;VALUE=DATE:20260210\r\nLANGUAGE:en\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

        $calendar = $this->parser->parse($icalData);

        $events = $calendar->getComponents('VEVENT');
        $this->assertCount(1, $events);

        $dtstart = $events[0]->getProperty('DTSTART');
        $this->assertNotNull($dtstart);
        $params = $dtstart->getParameters();
        $this->assertArrayHasKey('VALUE', $params);
        $this->assertEquals('DATE', $params['VALUE']);
    }
}
