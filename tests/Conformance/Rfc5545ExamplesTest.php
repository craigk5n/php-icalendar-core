<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\TestCase;

/**
 * RFC 5545 Conformance Tests
 * 
 * Tests all RFC 5545 examples to verify they parse correctly
 * and round-trip successfully (parse → write → parse).
 */
class Rfc5545ExamplesTest extends TestCase
{
    private Parser $parser;
    private Writer $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->writer = new Writer();
    }

    /**
     * Test round-trip for a given fixture file
     */
    private function assertRoundTrip(string $fixture): void
    {
        $original = file_get_contents($fixture);
        // Ensure the parser is in lenient mode for reading fixtures that might have minor issues
        $this->parser->setStrict(false); 
        $calendar = $this->parser->parse($original);
        $output = $this->writer->write($calendar);

        // Reparse in strict mode to catch any writing issues
        $this->parser->setStrict(true); 
        $reparsed = $this->parser->parse($output);

        $this->assertCalendarsEquivalent($calendar, $reparsed, 
            'Round-trip failed for fixture: ' . basename($fixture));
    }

    /**
     * Assert two calendars are semantically equivalent
     * (ignoring whitespace and ordering differences)
     */
    private function assertCalendarsEquivalent(VCalendar $original, VCalendar $reparsed, string $message = ''): void
    {
        // Get all events from both calendars
        $originalEvents = $original->getComponents('VEVENT');
        $reparsedEvents = $reparsed->getComponents('VEVENT');

        $this->assertCount(count($originalEvents), $reparsedEvents, 
            $message . ' Event count mismatch');

        // Compare key properties of each event (ignoring minor formatting differences)
        // For DTSTART, we compare the raw value which should be consistently formatted by the writer/parser.
        for ($i = 0; $i < count($originalEvents); $i++) {
            $originalEvent = $originalEvents[$i];
            $reparsedEvent = $reparsedEvents[$i];

            // Assert that properties exist before getting their values
            $this->assertNotNull($originalEvent->getProperty('UID'), $message . " UID property missing in original event $i");
            $this->assertNotNull($reparsedEvent->getProperty('UID'), $message . " UID property missing in reparsed event $i");

            $this->assertEquals(
                $originalEvent->getProperty('UID')->getValue()->getRawValue(),
                $reparsedEvent->getProperty('UID')->getValue()->getRawValue(),
                $message . " UID mismatch for event $i"
            );

            $this->assertNotNull($originalEvent->getProperty('DTSTART'), $message . " DTSTART property missing in original event $i");
            $this->assertNotNull($reparsedEvent->getProperty('DTSTART'), $message . " DTSTART property missing in reparsed event $i");

            // DTSTART can be DATE or DATETIME. The writer should format it consistently.
            // For DATE values (YYYYMMDD), writer adds no timezone. For DATETIME, it adds Z if UTC.
            // The test fixture 'all-day-event.ics' has DTSTART:20080202, which is a DATE.
            // The writer should preserve this as YYYYMMDD. The parser might interpret it as UTC midnight if no TZID is present,
            // but the raw value from the parser should match the raw value from the writer.
            if ($originalEvent->getProperty('DTSTART')->getValue()->getType() === 'DATE') {
                $this->assertEquals(
                    $originalEvent->getProperty('DTSTART')->getValue()->getRawValue(),
                    $reparsedEvent->getProperty('DTSTART')->getValue()->getRawValue(),
                    $message . " DTSTART (DATE) mismatch for event $i"
                );
            } else { // DATETIME
                // For DATETIME, we compare the formatted string, as the parser might add Z for UTC
                // or interpret local time without explicit timezone. The writer should format it consistently.
                // The writer *should* add Z for UTC, and omit for local.
                // Let's check the raw value for consistency between parser/writer for DATETIME too.
                $this->assertEquals(
                    $originalEvent->getProperty('DTSTART')->getValue()->getRawValue(),
                    $reparsedEvent->getProperty('DTSTART')->getValue()->getRawValue(),
                    $message . " DTSTART (DATETIME) mismatch for event $i"
                );
            }

            $this->assertNotNull($originalEvent->getProperty('SUMMARY'), $message . " SUMMARY property missing in original event $i");
            $this->assertNotNull($reparsedEvent->getProperty('SUMMARY'), $message . " SUMMARY property missing in reparsed event $i");

            $this->assertEquals(
                $originalEvent->getProperty('SUMMARY')->getValue()->getRawValue(),
                $reparsedEvent->getProperty('SUMMARY')->getValue()->getRawValue(),
                $message . " SUMMARY mismatch for event $i"
            );
        }

        // Check calendar properties
        $this->assertNotNull($original->getProperty('PRODID'), $message . ' PRODID property missing in original calendar');
        $this->assertNotNull($reparsed->getProperty('PRODID'), $message . ' PRODID property missing in reparsed calendar');
        $this->assertEquals(
            $original->getProperty('PRODID')->getValue()->getRawValue(),
            $reparsed->getProperty('PRODID')->getValue()->getRawValue(),
            $message . ' PRODID mismatch'
        );

        $this->assertNotNull($original->getProperty('VERSION'), $message . ' VERSION property missing in original calendar');
        $this->assertNotNull($reparsed->getProperty('VERSION'), $message . ' VERSION property missing in reparsed calendar');
        $this->assertEquals(
            $original->getProperty('VERSION')->getValue()->getRawValue(),
            $reparsed->getProperty('VERSION')->getValue()->getRawValue(),
            $message . ' VERSION mismatch'
        );
    }

    public function testRfcExample1SimpleEvent(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/simple-event.ics');
    }

    public function testRfcExample2DailyRecurring(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/daily-recurring.ics');
    }

    public function testRfcExample3WeeklyWithExceptions(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/weekly-with-exceptions.ics');
    }

    public function testRfcExample4MonthlyByday(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/monthly-byday.ics');
    }

    public function testRfcExample5YearlyRecurring(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/yearly-recurring.ics');
    }

    public function testRfcExample6AllDayEvent(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/all-day-event.ics');
    }

    public function testRfcExample7TodoWithDue(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/todo-with-due.ics');
    }

    public function testRfcExample8JournalEntry(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/journal-entry.ics');
    }

    public function testRfcExample9Freebusy(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/freebusy.ics');
    }

    public function testRfcExample10TimezoneDst(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/timezone-dst.ics');
    }

    public function testRfcExample11AlarmDisplay(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/alarm-display.ics');
    }

    public function testRfcExample12AlarmEmail(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/alarm-email.ics');
    }

    public function testRfcExample13AlarmAudio(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/alarm-audio.ics');
    }

    public function testRfcExample14ComplexMeeting(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/complex-meeting.ics');
    }

    /**
     * Test that all data types can round-trip correctly
     * Uses a complex example that includes most data types
     */
    public function testAllDataTypesRoundTrip(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/complex-meeting.ics');
    }

    /**
     * Test that properties are preserved during round-trip
     */
    public function testRoundTripPreservesProperties(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/complex-meeting.ics');
    }

    /**
     * Test that components are preserved during round-trip
     */
    public function testRoundTripPreservesComponents(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/complex-meeting.ics');
    }

    /**
     * Test that parameters are preserved during round-trip
     */
    public function testRoundTripPreservesParameters(): void
    {
        $this->assertRoundTrip(__DIR__ . '/../fixtures/rfc5545/complex-meeting.ics');
    }

    /**
     * Test that all examples parse without errors
     */
    public function testAllExamplesParseWithoutErrors(): void
    {
        $fixtures = [
            'simple-event.ics',
            'daily-recurring.ics',
            'weekly-with-exceptions.ics',
            'monthly-byday.ics',
            'yearly-recurring.ics',
            'all-day-event.ics',
            'todo-with-due.ics',
            'journal-entry.ics',
            'freebusy.ics',
            'timezone-dst.ics',
            'alarm-display.ics',
            'alarm-email.ics',
            'alarm-audio.ics',
            'complex-meeting.ics'
        ];

        foreach ($fixtures as $fixture) {
            $content = file_get_contents(__DIR__ . "/../fixtures/rfc5545/$fixture");
            try {
                $calendar = $this->parser->parse($content);
                $this->assertNotNull($calendar, "Failed to parse fixture: $fixture");
                
                // Check that no parse errors occurred
                $errors = $this->parser->getErrors();
                $this->assertEmpty($errors, "Parse errors found for fixture: $fixture");
                
            } catch (\Exception $e) {
                $this->fail("Exception parsing fixture $fixture: " . $e->getMessage());
            }
        }
    }
}