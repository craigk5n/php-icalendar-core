<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VTimezone;
use Icalendar\Parser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A VTIMEZONE observance defines *recurring* transitions via RRULE
 * (RFC 5545 §3.6.5). buildTransitions() recorded one transition per observance,
 * taken from its DTSTART, and ignored RRULE entirely -- so the table described
 * a single year and every later year inherited whichever transition happened to
 * be last.
 *
 * The failure was silent: no exception, no warning, just a wrong instant. Any
 * date after the final literal transition stuck on that observance's offset
 * (summer 2028 resolving to EST), and any date before the first resolved to
 * offset 0 / "UTC" rather than the zone's standard time.
 *
 * The fixture used here is the pre-2007 US rule already in tests/fixtures:
 * DST begins the first Sunday in April and ends the first Sunday in October,
 * both recurring yearly from 2005.
 */
class VTimezoneRecurringTransitionTest extends TestCase
{
    private const EDT = -14400; // -04:00
    private const EST = -18000; // -05:00

    private function timezone(): VTimezone
    {
        $ics = (string) file_get_contents(__DIR__ . '/../fixtures/rfc5545/timezone-dst.ics');
        $calendar = (new Parser(Parser::LENIENT))->parse($ics);

        $timezones = $calendar->getComponents('VTIMEZONE');
        self::assertNotEmpty($timezones, 'fixture must contain a VTIMEZONE');
        $timezone = $timezones[0];
        self::assertInstanceOf(VTimezone::class, $timezone);

        return $timezone;
    }

    private function at(string $iso): \DateTimeImmutable
    {
        return new \DateTimeImmutable($iso, new \DateTimeZone('UTC'));
    }

    /**
     * Years beyond the literal DTSTART are the whole point: each is reachable
     * only by expanding the yearly RRULE.
     *
     * @return array<string, array{string, int, string}>
     */
    public static function acrossYearsProvider(): array
    {
        return [
            // The DTSTART year itself, which worked before.
            'summer 2005' => ['2005-07-01T12:00:00', self::EDT, 'EDT'],
            'winter 2005' => ['2005-12-01T12:00:00', self::EST, 'EST'],
            // Every one of these needed RRULE expansion.
            'summer 2006' => ['2006-07-01T12:00:00', self::EDT, 'EDT'],
            'winter 2006' => ['2006-01-15T12:00:00', self::EST, 'EST'],
            'summer 2010' => ['2010-07-01T12:00:00', self::EDT, 'EDT'],
            'winter 2010' => ['2010-12-15T12:00:00', self::EST, 'EST'],
            'summer 2028' => ['2028-07-01T12:00:00', self::EDT, 'EDT'],
            'winter 2028' => ['2028-12-15T12:00:00', self::EST, 'EST'],
        ];
    }

    #[DataProvider('acrossYearsProvider')]
    public function testOffsetIsCorrectInEveryRecurringYear(string $iso, int $offset, string $name): void
    {
        $timezone = $this->timezone();

        self::assertSame($offset, $timezone->getOffsetAt($this->at($iso)), "offset at {$iso}");
        self::assertSame($name, $timezone->getAbbreviationAt($this->at($iso)), "name at {$iso}");
    }

    /**
     * Before the first transition the zone is in standard time, which is what
     * the earliest observance's TZOFFSETFROM records. Returning 0 claimed UTC.
     */
    public function testBeforeTheFirstTransitionUsesStandardTimeNotUtc(): void
    {
        $timezone = $this->timezone();
        $before = $this->at('2004-01-01T12:00:00');

        self::assertSame(self::EST, $timezone->getOffsetAt($before));
        self::assertNotSame(0, $timezone->getOffsetAt($before), 'must not fall back to UTC');
        self::assertSame('EST', $timezone->getAbbreviationAt($before));
    }

    /**
     * A recurring zone must produce many transitions, not one per observance.
     */
    public function testRecurringObservancesExpandToManyTransitions(): void
    {
        $timezone = $this->timezone();
        $timezone->getOffsetAt($this->at('2028-07-01T12:00:00'));

        self::assertGreaterThan(
            10,
            count($timezone->getTransitions()),
            'a yearly RRULE spanning 2005-2028 must yield many transitions, not 2'
        );
    }

    /** Transitions must stay ordered, since lookup scans them in sequence. */
    public function testTransitionsRemainSortedAfterExpansion(): void
    {
        $timezone = $this->timezone();
        $timezone->getOffsetAt($this->at('2020-07-01T12:00:00'));

        $times = array_column($timezone->getTransitions(), 'time');
        $sorted = $times;
        sort($sorted);

        self::assertSame($sorted, $times);
    }

    /**
     * An observance with no RRULE must keep behaving exactly as before: one
     * transition at its DTSTART.
     */
    public function testNonRecurringObservancesAreUnchanged(): void
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//t//t//EN\r\n"
            . "BEGIN:VTIMEZONE\r\nTZID:Etc/Test\r\n"
            . "BEGIN:STANDARD\r\nDTSTART:20261101T020000Z\r\n"
            . "TZOFFSETFROM:-0400\r\nTZOFFSETTO:-0500\r\nTZNAME:XST\r\nEND:STANDARD\r\n"
            . "END:VTIMEZONE\r\nEND:VCALENDAR\r\n";

        $calendar = (new Parser(Parser::LENIENT))->parse($ics);
        $timezone = $calendar->getComponents('VTIMEZONE')[0];
        self::assertInstanceOf(VTimezone::class, $timezone);

        self::assertCount(1, $timezone->getTransitions());
        self::assertSame(-18000, $timezone->getOffsetAt($this->at('2026-12-01T12:00:00')));
    }
}
