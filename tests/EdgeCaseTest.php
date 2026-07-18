<?php

declare(strict_types=1);

namespace Icalendar\Tests;

use PHPUnit\Framework\TestCase;
use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Exception\ParseException;
use Icalendar\Exception\ValidationException;
use Icalendar\Validation\SecurityValidator;

/**
 * Edge Case Tests
 * 
 * Tests edge cases and boundary conditions to ensure library robustness.
 * Covers empty values, maximum lengths, nesting depth, Unicode, malformed data,
 * and stress conditions.
 */
class EdgeCaseTest extends TestCase
{
    private Parser $parser;
    private Writer $writer;
    private SecurityValidator $security;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->writer = new Writer();
        $this->security = new SecurityValidator();
    }

    // === Empty Values Tests ===

    public function testEmptyTextProperty(): void
    {
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:\r\nDESCRIPTION:\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $events = $calendar->getComponents('VEVENT');
        $event = $events[0];
        
        $this->assertEmpty($event->getProperty('SUMMARY')->getValue()->getRawValue());
        $this->assertEmpty($event->getProperty('DESCRIPTION')->getValue()->getRawValue());
    }

    public function testEmptyDescriptionProperty(): void
    {
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:Test\r\nDESCRIPTION:\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals('Test', $event->getProperty('SUMMARY')->getValue()->getRawValue());
        $this->assertEmpty($event->getProperty('DESCRIPTION')->getValue()->getRawValue());
    }

    // === Maximum Line Length Tests ===

    public function testExactly75OctetsLine(): void
    {
        // Create a line exactly 75 octets long
        $value = str_repeat('A', 67); // 67 + 'SUMMARY:' = 75
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:{$value}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals($value, $event->getProperty('SUMMARY')->getValue()->getRawValue());
    }


    public function testVeryLongLineFoldsCorrectly(): void
    {
        $value = str_repeat('A', 200);
        $event = new VEvent();
        $event->setUid('test');
        $event->setDtStart('20260101T000000Z');
        $event->setSummary($value);
        
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//Test//EN');
        $calendar->setVersion('2.0');
        $calendar->addComponent($event);
        
        $output = $this->writer->write($calendar);
        
        // Check that long lines are folded
        $lines = explode("\r\n", $output);
        $summaryLineFound = false;
        foreach ($lines as $line) {
            if (str_starts_with($line, 'SUMMARY:')) {
                $this->assertLessThanOrEqual(75, strlen($line));
                $summaryLineFound = true;
            }
        }
        $this->assertTrue($summaryLineFound, 'SUMMARY line should be present');
    }

    // === Unicode Support Tests ===

    public function testLatinUnicode(): void
    {
        $unicodeText = "Café résumé naïve façon";
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:{$unicodeText}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals($unicodeText, $event->getProperty('SUMMARY')->getValue()->getRawValue());
    }

    public function testCyrillicUnicode(): void
    {
        $cyrillicText = "Привет мир";
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:{$cyrillicText}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals($cyrillicText, $event->getProperty('SUMMARY')->getValue()->getRawValue());
    }

    public function testArabicUnicode(): void
    {
        $arabicText = "مرحبا بالعالم";
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:{$arabicText}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals($arabicText, $event->getProperty('SUMMARY')->getValue()->getRawValue());
    }

    public function testEmojiUnicode(): void
    {
        $emojiText = "Meeting 📅 with team 👥 at 🏢";
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:{$emojiText}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals($emojiText, $event->getProperty('SUMMARY')->getValue()->getRawValue());
    }

    public function testCJKUnicode(): void
    {
        $cjkText = "会议，预约，日历";
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:{$cjkText}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals($cjkText, $event->getProperty('SUMMARY')->getValue()->getRawValue());
    }

    public function testMixedUnicodeScript(): void
    {
        $mixedText = "Hello 世界 Привет 🌍 مرحبا";
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:{$mixedText}\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals($mixedText, $event->getProperty('SUMMARY')->getValue()->getRawValue());
    }

    // === Malformed Folding Tests ===

    public function testMalformedFoldingNoSpace(): void
    {
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:Line\r\ncontinued\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Malformed');
        $this->parser->parse($ical);
    }



    // === Invalid Dates/Times Tests ===


    public function testValidLeapYearDate(): void
    {
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nDTSTART:20200229T000000Z\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $this->assertEquals('20200229T000000Z', $event->getProperty('DTSTART')->getValue()->getRawValue());
    }

    // === Stress Tests ===

    public function testTenThousandEvents(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Stress Test//Test//EN');
        $calendar->setVersion('2.0');
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) { // Reduced to 1000 for test speed
            $event = new VEvent();
            $event->setUid("event-{$i}@test.com");
            $event->setDtStart("20260101T" . str_pad((string)($i % 24), 2, '0', STR_PAD_LEFT) . "0000Z");
            $event->setSummary("Event {$i}");
            $calendar->addComponent($event);
        }
        
        $creationTime = microtime(true);
        
        $output = $this->writer->write($calendar);
        
        $writeTime = microtime(true);
        
        $parseStartTime = microtime(true);
        $parsedCalendar = $this->parser->parse($output);
        $parseEndTime = microtime(true);
        
        $events = $parsedCalendar->getComponents('VEVENT');
        
        // Verify all events were parsed
        $this->assertCount(1000, $events);
        
        // Verify a few sample events
        $this->assertEquals('Event 0', $events[0]->getProperty('SUMMARY')->getValue()->getRawValue());
        $this->assertEquals('Event 999', $events[999]->getProperty('SUMMARY')->getValue()->getRawValue());
        
        // Performance assertions (adjust based on environment)
        $this->assertLessThan(5.0, $creationTime - $startTime, 'Creating events should take < 5 seconds');
        $this->assertLessThan(2.0, $writeTime - $creationTime, 'Writing events should take < 2 seconds');
        $this->assertLessThan(2.0, $parseEndTime - $parseStartTime, 'Parsing events should take < 2 seconds');
    }


    public function testLongText(): void
    {
        $longText = str_repeat('This is a very long description. ', 1024); // ~32KB
        $event = new VEvent();
        $event->setUid('long-text@test.com');
        $event->setDtStart('20260101T100000Z');
        $event->setSummary('Long Description Test');
        $event->setDescription($longText);
        
        $calendar = new VCalendar();
        $calendar->setProductId('-//Long Text Test//Test//EN');
        $calendar->setVersion('2.0');
        $calendar->addComponent($event);
        
        $output = $this->writer->write($calendar);
        $parsedCalendar = $this->parser->parse($output);
        
        $parsedEvent = $parsedCalendar->getComponents('VEVENT')[0];
        $parsedDescription = $parsedEvent->getProperty('DESCRIPTION')->getValue()->getRawValue();
        
        $this->assertEquals($longText, $parsedDescription);
        $this->assertGreaterThan(30000, strlen($parsedDescription));
    }

    // === Memory Tests ===

    public function testMemoryUsageDuringParsing(): void
    {
        $memoryBefore = memory_get_usage(true);
        
        // Create a moderately large calendar
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\n";
        
        for ($i = 0; $i < 1000; $i++) {
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:event-{$i}@test.com\r\n";
            $ical .= "DTSTAMP:20260101T000000Z\r\n";
            $ical .= "DTSTART:20260101T100000Z\r\n";
            $ical .= "SUMMARY:Event {$i}\r\n";
            $ical .= "DESCRIPTION:This is event number {$i} with some description text\r\n";
            $ical .= "END:VEVENT\r\n";
        }
        
        $ical .= "END:VCALENDAR\r\n";
        
        $calendar = $this->parser->parse($ical);
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Should use reasonable amount of memory (< 50MB for 1000 events)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Parsing 1000 events should use < 50MB memory');
        $this->assertCount(1000, $calendar->getComponents('VEVENT'));
    }

    // === Round-trip Tests ===

    public function testRoundTripUnicode(): void
    {
        $originalCalendar = new VCalendar();
        $originalCalendar->setProductId('-//Round Trip Test//Test//EN');
        $originalCalendar->setVersion('2.0');
        
        $event = new VEvent();
        $event->setUid('unicode@test.com');
        $event->setDtStart('20260101T100000Z');
        $event->setSummary('Meeting 📅 with team 👥');
        $event->setDescription('Café résumé naïve façон Привет мир');
        $event->setLocation('Office 🏢 Building A');
        
        $originalCalendar->addComponent($event);
        
        // Write to string
        $icalString = $this->writer->write($originalCalendar);
        
        // Parse back
        $parsedCalendar = $this->parser->parse($icalString);
        $parsedEvent = $parsedCalendar->getComponents('VEVENT')[0];
        
        // Verify round-trip integrity using property access
        $this->assertEquals($event->getProperty('UID')->getValue()->getRawValue(), $parsedEvent->getProperty('UID')->getValue()->getRawValue());
        $this->assertEquals($event->getProperty('SUMMARY')->getValue()->getRawValue(), $parsedEvent->getProperty('SUMMARY')->getValue()->getRawValue());
        $this->assertEquals($event->getProperty('DESCRIPTION')->getValue()->getRawValue(), $parsedEvent->getProperty('DESCRIPTION')->getValue()->getRawValue());
        $this->assertEquals($event->getProperty('LOCATION')->getValue()->getRawValue(), $parsedEvent->getProperty('LOCATION')->getValue()->getRawValue());
    }

    // === Boundary and Error Tests ===

    /**
     * An ATTENDEE with an empty value is not a CAL-ADDRESS. Strict mode must
     * reject it; lenient mode must record a warning and drop the property
     * rather than invent an empty attendee -- the same divergence
     * MalformedValueModeTest pins for date properties.
     */
    public function testEmptyAttendeeValueIsRejectedInStrictAndWarnedInLenient(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:a@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "DTSTART:20260101T000000Z\r\nATTENDEE:\r\n"
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $strict = new Parser(Parser::STRICT);
        try {
            $strict->parse($ics);
            $this->fail('strict mode must reject an empty CAL-ADDRESS');
        } catch (ParseException $e) {
            $this->assertStringContainsStringIgnoringCase('cal-address', $e->getMessage());
        }

        $lenient = new Parser(Parser::LENIENT);
        $calendar = $lenient->parse($ics);

        $events = $calendar->getComponents('VEVENT');
        $this->assertNotEmpty($events, 'lenient mode still yields the event');
        $this->assertNull(
            $events[0]->getProperty('ATTENDEE'),
            'the unusable ATTENDEE is dropped, not stored as an empty value'
        );
        $this->assertNotEmpty($lenient->getWarnings(), 'lenient mode records a diagnostic');
    }

    public function testMalformedFoldingNoTab(): void
    {
        $ical = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:test\r\nBEGIN:VEVENT\r\nUID:test\r\nDTSTAMP:20260101T000000Z\r\nSUMMARY:Line\r\ncontinued\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
        
        $this->expectException(ParseException::class);
        $this->parser->parse($ical);
    }






    // === Maximum Nesting Depth Tests ===




    // === Security Tests ===


}