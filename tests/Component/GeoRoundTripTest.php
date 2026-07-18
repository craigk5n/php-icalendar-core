<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Parser\Parser;
use Icalendar\Writer\PropertyWriter;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\TestCase;

/**
 * GEO is a structured `latitude ";" longitude` value (RFC 5545 §3.8.1.6). Two
 * defects met at this property:
 *
 * - Parse side: GEO fell through to the TEXT default and TextParser cannot fail,
 *   so `GEO:total garbage` was accepted with no warning.
 * - Write side: setGeo() stored the value as TEXT, so TextWriter escaped the
 *   structural semicolon and emitted `GEO:37.386013\;-122.082932` -- a value a
 *   conformant reader can no longer split into two floats.
 *
 * These tests pin both: invalid GEO is now reported, and a valid GEO round-trips
 * with a literal semicolon.
 */
class GeoRoundTripTest extends TestCase
{
    public function testSetGeoWritesALiteralSemicolon(): void
    {
        $event = new VEvent();
        $event->setGeo(37.386013, -122.082932);

        $line = (new PropertyWriter())->write($event->getProperty('GEO'));

        self::assertSame('GEO:37.386013;-122.082932', $line);
        self::assertStringNotContainsString('\\;', $line);
    }

    public function testValidGeoRoundTrips(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//test//test//EN')->setVersion('2.0');
        $event = new VEvent();
        $event->setUid('geo@example.com')
            ->setDtStamp('20260101T000000Z')
            ->setDtStart('20260101T000000Z')
            ->setSummary('s')
            ->setGeo(37.386013, -122.082932);
        $calendar->addComponent($event);

        $ics = (new Writer())->write($calendar);
        self::assertStringContainsString('GEO:37.386013;-122.082932', $ics);

        $parsed = (new Parser())->parse($ics);
        $events = $parsed->getComponents('VEVENT');
        self::assertNotEmpty($events);
        $reparsed = $events[0];
        self::assertInstanceOf(VEvent::class, $reparsed);

        self::assertSame(
            ['latitude' => 37.386013, 'longitude' => -122.082932],
            $reparsed->getGeo()
        );
    }

    public function testInvalidGeoIsReportedInLenientMode(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:geo@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "DTSTART:20260101T000000Z\r\nSUMMARY:s\r\nGEO:total garbage\r\n"
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $parser = new Parser(Parser::LENIENT);
        $parser->parse($ics);

        $warnings = $parser->getWarnings();
        $geoWarnings = array_filter(
            $warnings,
            static fn ($w): bool => stripos($w->message, 'geo') !== false
                || stripos((string) $w->property, 'geo') !== false
        );

        self::assertNotEmpty($geoWarnings, 'invalid GEO should produce a warning in lenient mode');
    }

    public function testInvalidGeoThrowsInStrictMode(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:geo@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . "DTSTART:20260101T000000Z\r\nSUMMARY:s\r\nGEO:91;0\r\n"
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $this->expectException(\Icalendar\Exception\ParseException::class);
        (new Parser(Parser::STRICT))->parse($ics);
    }
}
