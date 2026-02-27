<?php

declare(strict_types=1);

namespace Icalendar\Tests\Fidelity;

use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\TestCase;

class RealWorldRoundTripTest extends TestCase
{
    private Parser $parser;
    private Writer $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->writer = new Writer();
    }

    public static function realWorldFilesProvider(): array
    {
        $fixturesDir = __DIR__ . '/../fixtures/real_world';
        $files = glob($fixturesDir . '/*.ics');
        
        $data = [];
        foreach ($files as $file) {
            $name = basename($file, '.ics');
            $data[$name] = [$file];
        }
        
        return $data;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('realWorldFilesProvider')]
    public function testRealWorldFileRoundTrip(string $icsPath): void
    {
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs, "Failed to read ICS file: {$icsPath}");

        $calendar = $this->parser->parse($originalIcs);
        $this->assertNotNull($calendar, "Failed to parse ICS file: {$icsPath}");

        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('BEGIN:VCALENDAR', $roundTripIcs);
        $this->assertStringContainsString('END:VCALENDAR', $roundTripIcs);
        $this->assertStringContainsString('VERSION:2.0', $roundTripIcs);
        $this->assertStringContainsString('PRODID:', $roundTripIcs);

        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed, "Failed to re-parse round-tripped ICS from: {$icsPath}");
    }

    public function testGoogleUsHolidaysParsesCorrectly(): void
    {
        $icsPath = __DIR__ . '/../fixtures/real_world/google_us_holidays.ics';
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs);

        $calendar = $this->parser->parse($originalIcs);
        
        $this->assertNotNull($calendar);
        
        $events = $calendar->getComponents('VEVENT');
        $this->assertGreaterThan(0, count($events), 'Should have at least one event');
    }

    public function testGoogleJapanHolidaysParsesCorrectly(): void
    {
        $icsPath = __DIR__ . '/../fixtures/real_world/google_japan_holidays.ics';
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs);

        $calendar = $this->parser->parse($originalIcs);
        
        $this->assertNotNull($calendar);
        
        $events = $calendar->getComponents('VEVENT');
        $this->assertGreaterThan(0, count($events), 'Should have at least one event');
    }

    public function testOfficeGermanyHolidaysParsesCorrectly(): void
    {
        $icsPath = __DIR__ . '/../fixtures/real_world/office_germany_holidays.ics';
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs);

        $calendar = $this->parser->parse($originalIcs);
        
        $this->assertNotNull($calendar);
        
        $events = $calendar->getComponents('VEVENT');
        $this->assertGreaterThan(0, count($events), 'Should have at least one event');
    }

    public function testGoogleMoonPhasesParsesCorrectly(): void
    {
        $icsPath = __DIR__ . '/../fixtures/real_world/google_moon_phases.ics';
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs);

        $calendar = $this->parser->parse($originalIcs);
        
        $this->assertNotNull($calendar);
        
        $events = $calendar->getComponents('VEVENT');
        $this->assertGreaterThan(0, count($events), 'Should have at least one event');
    }

    public function testRealWorldFileHandlesXProperties(): void
    {
        $icsPath = __DIR__ . '/../fixtures/real_world/google_us_holidays.ics';
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs);

        $calendar = $this->parser->parse($originalIcs);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertStringContainsString('X-WR-CALNAME', $roundTripIcs, 'Should preserve X-WR-CALNAME');
        $this->assertStringContainsString('X-WR-TIMEZONE', $roundTripIcs, 'Should preserve X-WR-TIMEZONE');
    }

    public function testRealWorldFileHandlesUnicode(): void
    {
        $icsPath = __DIR__ . '/../fixtures/real_world/google_japan_holidays.ics';
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs);

        $calendar = $this->parser->parse($originalIcs);
        $roundTripIcs = $this->writer->write($calendar);
        
        $this->assertNotNull($calendar);
        
        $reParsed = $this->parser->parse($roundTripIcs);
        $this->assertNotNull($reParsed);
    }

    public function testRealWorldFilePreservesEventCount(): void
    {
        $icsPath = __DIR__ . '/../fixtures/real_world/google_canada_holidays.ics';
        $originalIcs = file_get_contents($icsPath);
        $this->assertNotFalse($originalIcs);

        $calendar = $this->parser->parse($originalIcs);
        $originalEventCount = count($calendar->getComponents('VEVENT'));
        
        $roundTripIcs = $this->writer->write($calendar);
        $reParsed = $this->parser->parse($roundTripIcs);
        $roundTripEventCount = count($reParsed->getComponents('VEVENT'));
        
        $this->assertEquals($originalEventCount, $roundTripEventCount, 'Event count should be preserved');
    }
}
