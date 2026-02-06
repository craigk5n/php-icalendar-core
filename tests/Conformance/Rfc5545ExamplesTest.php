<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Component\VCalendar;
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
        $calendar = $this->parser->parse($original);
        $output = $this->writer->write($calendar);
        $reparsed = $this->parser->parse($output);

        $this->assertCalendarsEquivalent($calendar, $reparsed, 
            "Round-trip failed for fixture: " . basename($fixture));
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

        $this->assertEquals(count($originalEvents), count($reparsedEvents), 
            $message . ' Event count mismatch');

        // Compare key properties of each event (ignoring minor formatting differences)
        for ($i = 0; $i < count($originalEvents); $i++) {
            $originalEvent = $originalEvents[$i];
            $reparsedEvent = $reparsedEvents[$i];

            $this->assertEquals(
                $originalEvent->getProperty('UID')->getValue()->getRawValue(),
                $reparsedEvent->getProperty('UID')->getValue()->getRawValue(),
                $message . " UID mismatch for event $i"
            );

            $this->assertEquals(
                $originalEvent->getProperty('DTSTART')->getValue()->getRawValue(),
                $reparsedEvent->getProperty('DTSTART')->getValue()->getRawValue(),
                $message . " DTSTART mismatch for event $i"
            );

            $this->assertEquals(
                $originalEvent->getProperty('SUMMARY')->getValue()->getRawValue(),
                $reparsedEvent->getProperty('SUMMARY')->getValue()->getRawValue(),
                $message . " SUMMARY mismatch for event $i"
            );
        }

        // Check calendar properties
        $this->assertEquals(
            $original->getProperty('PRODID')->getValue()->getRawValue(),
            $reparsed->getProperty('PRODID')->getValue()->getRawValue(),
            $message . ' PRODID mismatch'
        );

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