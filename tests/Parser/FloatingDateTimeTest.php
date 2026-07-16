<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Parser\Parser;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A floating DATE-TIME must not be promoted to UTC.
 *
 * RFC 5545 §3.3.5 gives DATE-TIME three forms: floating (no suffix), UTC
 * (trailing Z) and zoned (TZID parameter). Floating means "10:00 wherever the
 * reader is"; UTC means a fixed instant that renders at a different hour for
 * every reader outside UTC. They are different events.
 *
 * Parser::formatParsedValue() inferred UTC-ness from the parsed value's
 * timezone *name*. DateTimeParser::parseLocal() builds a DateTimeImmutable with
 * no explicit zone, so a floating value silently inherits PHP's date.timezone --
 * and when that is UTC (the default on most servers, containers and CI), the
 * value came back as UTC and gained a Z. The corruption therefore only appeared
 * in the *common production configuration*, which is why it went unseen.
 *
 * These tests pin the timezone explicitly so they fail on any host, rather than
 * depending on the runner's date.timezone.
 */
class FloatingDateTimeTest extends TestCase
{
    /** @var non-empty-string */
    private string $originalTimezone;

    #[\Override]
    protected function setUp(): void
    {
        $timezone = date_default_timezone_get();
        $this->originalTimezone = $timezone !== '' ? $timezone : 'UTC';
    }

    #[\Override]
    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
    }

    private function calendarWith(string $dtstart): string
    {
        return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:test-uid\r\nDTSTAMP:20260716T151122Z\r\n"
            . "{$dtstart}\r\nSUMMARY:s\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    private function parseDtstart(string $dtstart): string
    {
        $calendar = (new Parser(Parser::STRICT))->parse($this->calendarWith($dtstart));

        return $calendar->getComponents()[0]
            ->getProperty('DTSTART')
            ->getValue()
            ->getRawValue();
    }

    /** @return array<string, array{non-empty-string}> */
    public static function timezoneProvider(): array
    {
        return [
            'utc host' => ['UTC'],
            'new york host' => ['America/New_York'],
            'tokyo host' => ['Asia/Tokyo'],
            'kolkata host (half-hour offset)' => ['Asia/Kolkata'],
        ];
    }

    /**
     * A floating value must stay floating no matter what the host's clock says.
     *
     * @param non-empty-string $timezone
     */
    #[DataProvider('timezoneProvider')]
    public function testFloatingDateTimeStaysFloating(string $timezone): void
    {
        date_default_timezone_set($timezone);

        $this->assertSame(
            '20260101T100000',
            $this->parseDtstart('DTSTART:20260101T100000'),
            "floating DTSTART was rewritten under date.timezone={$timezone}"
        );
    }

    /**
     * A UTC value must keep its Z, on every host.
     *
     * @param non-empty-string $timezone
     */
    #[DataProvider('timezoneProvider')]
    public function testUtcDateTimeKeepsZ(string $timezone): void
    {
        date_default_timezone_set($timezone);

        $this->assertSame(
            '20260101T100000Z',
            $this->parseDtstart('DTSTART:20260101T100000Z')
        );
    }

    /**
     * A zoned value carries its offset in TZID and must not gain a Z.
     *
     * @param non-empty-string $timezone
     */
    #[DataProvider('timezoneProvider')]
    public function testZonedDateTimeDoesNotGainZ(string $timezone): void
    {
        date_default_timezone_set($timezone);

        $this->assertSame(
            '20260101T100000',
            $this->parseDtstart('DTSTART;TZID=America/New_York:20260101T100000')
        );
    }

    /** Parsing must not depend on the host timezone at all. */
    public function testParseIsHostTimezoneIndependent(): void
    {
        $results = [];
        foreach (['UTC', 'America/New_York', 'Asia/Tokyo'] as $timezone) {
            date_default_timezone_set($timezone);
            $results[$timezone] = $this->parseDtstart('DTSTART:20260101T100000');
        }

        $this->assertSame(
            [
                'UTC' => '20260101T100000',
                'America/New_York' => '20260101T100000',
                'Asia/Tokyo' => '20260101T100000',
            ],
            $results
        );
    }

    /**
     * The full parse -> write cycle must preserve the floating form.
     *
     * @param non-empty-string $timezone
     */
    #[DataProvider('timezoneProvider')]
    public function testFloatingSurvivesRoundTrip(string $timezone): void
    {
        date_default_timezone_set($timezone);

        $calendar = (new Parser(Parser::STRICT))->parse(
            $this->calendarWith('DTSTART:20260101T100000')
        );
        $output = (new Writer())->write($calendar);

        $this->assertStringContainsString("DTSTART:20260101T100000\r\n", $output);
        $this->assertStringNotContainsString('DTSTART:20260101T100000Z', $output);
    }

    /** PERIOD values are UTC per RFC 5545 §3.3.9 and must keep their Z. */
    public function testPeriodKeepsUtcDesignators(): void
    {
        date_default_timezone_set('UTC');

        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VFREEBUSY\r\nUID:test-uid\r\nDTSTAMP:20260716T151122Z\r\n"
            . "FREEBUSY:19970101T180000Z/19970102T070000Z\r\n"
            . "END:VFREEBUSY\r\nEND:VCALENDAR\r\n";

        $calendar = (new Parser(Parser::STRICT))->parse($ics);
        $freebusy = $calendar->getComponents()[0]->getProperty('FREEBUSY');

        $this->assertSame(
            '19970101T180000Z/19970102T070000Z',
            $freebusy->getValue()->getRawValue()
        );
    }
}
