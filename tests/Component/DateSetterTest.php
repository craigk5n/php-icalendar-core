<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\Available;
use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VAvailability;
use Icalendar\Component\VEvent;
use Icalendar\Component\VFreeBusy;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTodo;
use Icalendar\Parser\Parser;
use Icalendar\Property\GenericProperty;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Date setters accept a DateTimeInterface and an optional parameters argument.
 *
 * The setters took a bare string and offered no way to attach TZID or VALUE, so
 * a caller with a real date object had to format it by hand, and a caller
 * wanting a zoned DTSTART had to drop to GenericProperty + setParameter(). This
 * covers the additive widening: string still works unchanged, a
 * DateTimeInterface is formatted, and $params attaches parameters.
 *
 * Scope note: this does not add validation. A malformed string still passes
 * through -- that is a separate, deliberately-deferred decision. A
 * DateTimeInterface simply cannot be malformed, which is the safer path the
 * widening opens up.
 */
class DateSetterTest extends TestCase
{
    private function dtStartValue(ComponentInterface $c): ?string
    {
        return $c->getProperty('DTSTART')?->getValue()->getRawValue();
    }

    /** @return array<string, array{callable(): ComponentInterface}> */
    public static function componentProvider(): array
    {
        return [
            'VEvent' => [static fn (): VEvent => new VEvent()],
            'VTodo' => [static fn (): VTodo => new VTodo()],
            'VJournal' => [static fn (): VJournal => new VJournal()],
            'VFreeBusy' => [static fn (): VFreeBusy => new VFreeBusy()],
            'VAvailability' => [static fn (): VAvailability => new VAvailability()],
            'Available' => [static fn (): Available => new Available()],
        ];
    }

    /** BC: a plain string still lands verbatim, no parameters. */
    #[DataProvider('componentProvider')]
    public function testStringStillWorks(callable $make): void
    {
        $c = $make();
        $c->setDtStart('20240101T100000Z');

        $this->assertSame('20240101T100000Z', $this->dtStartValue($c));
        $this->assertSame([], $c->getProperty('DTSTART')?->getParameters());
    }

    #[DataProvider('componentProvider')]
    public function testStringWithTzidParameter(callable $make): void
    {
        $c = $make();
        $c->setDtStart('20240101T100000', ['TZID' => 'America/New_York']);

        $this->assertSame('20240101T100000', $this->dtStartValue($c));
        $this->assertSame('America/New_York', $c->getProperty('DTSTART')?->getParameters()['TZID'] ?? null);
    }

    /** A UTC DateTimeInterface serialises with a Z and no parameters. */
    #[DataProvider('componentProvider')]
    public function testUtcDateTimeSerialisesWithZ(callable $make): void
    {
        $c = $make();
        $c->setDtStart(new \DateTimeImmutable('2024-01-01 10:00:00', new \DateTimeZone('UTC')));

        $this->assertSame('20240101T100000Z', $this->dtStartValue($c));
    }

    /** A non-UTC instant with no TZID is converted to UTC (same instant). */
    #[DataProvider('componentProvider')]
    public function testNonUtcDateTimeIsConvertedToUtc(callable $make): void
    {
        $c = $make();
        // 10:00 in New York (UTC-5 in January) is 15:00 UTC.
        $c->setDtStart(new \DateTimeImmutable('2024-01-01 10:00:00', new \DateTimeZone('America/New_York')));

        $this->assertSame('20240101T150000Z', $this->dtStartValue($c));
    }

    /** A DateTimeInterface with a TZID keeps wall-clock time and drops the Z. */
    #[DataProvider('componentProvider')]
    public function testDateTimeWithTzidIsLocalWallClock(callable $make): void
    {
        $c = $make();
        $c->setDtStart(
            new \DateTimeImmutable('2024-01-01 10:00:00', new \DateTimeZone('America/New_York')),
            ['TZID' => 'America/New_York']
        );

        $this->assertSame('20240101T100000', $this->dtStartValue($c));
        $this->assertSame('America/New_York', $c->getProperty('DTSTART')?->getParameters()['TZID'] ?? null);
    }

    /** VALUE=DATE formats the DateTimeInterface as a bare date. */
    #[DataProvider('componentProvider')]
    public function testDateTimeWithValueDate(callable $make): void
    {
        $c = $make();
        $c->setDtStart(new \DateTimeImmutable('2024-01-01 10:00:00', new \DateTimeZone('UTC')), ['VALUE' => 'DATE']);

        $this->assertSame('20240101', $this->dtStartValue($c));
        $this->assertSame('DATE', $c->getProperty('DTSTART')?->getParameters()['VALUE'] ?? null);
    }

    /** The VALUE/TZID format decision is case-insensitive on the key. */
    public function testParameterKeyIsCaseInsensitiveForFormatting(): void
    {
        $c = new VEvent();
        $c->setDtStart(new \DateTimeImmutable('2024-01-01 10:00:00', new \DateTimeZone('UTC')), ['value' => 'DATE']);

        $this->assertSame('20240101', $this->dtStartValue($c));
    }

    #[DataProvider('componentProvider')]
    public function testSetterIsFluent(callable $make): void
    {
        $c = $make();
        $this->assertSame($c, $c->setDtStart('20240101T100000Z'));
    }

    #[DataProvider('componentProvider')]
    public function testSettingTwiceReplaces(callable $make): void
    {
        $c = $make();
        $c->setDtStart('20240101T100000Z');
        $c->setDtStart(new \DateTimeImmutable('2024-02-02 12:00:00', new \DateTimeZone('UTC')));

        $this->assertSame('20240202T120000Z', $this->dtStartValue($c));
        $this->assertCount(1, $c->getAllProperties('DTSTART'));
    }

    /** The zoned form must survive a write -> parse round trip. */
    public function testZonedDtStartRoundTrips(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240101T000000Z');
        $event->setUid('test-uid');
        $event->setDtStart(
            new \DateTimeImmutable('2024-01-01 10:00:00', new \DateTimeZone('America/New_York')),
            ['TZID' => 'America/New_York']
        );

        $calendar = new \Icalendar\Component\VCalendar();
        $calendar->addProperty(GenericProperty::create('PRODID', '-//test//test//EN'));
        $calendar->addProperty(GenericProperty::create('VERSION', '2.0'));
        $calendar->addComponent($event);

        $ics = (new Writer())->write($calendar);
        $this->assertStringContainsString('DTSTART;TZID=America/New_York:20240101T100000', $ics);

        $reparsed = (new Parser(Parser::STRICT))->parse($ics);
        $dtstart = $reparsed->getComponents()[0]->getProperty('DTSTART');
        $this->assertNotNull($dtstart);
        $this->assertSame('America/New_York', $dtstart->getParameters()['TZID'] ?? null);
    }

    /** The widening applies to the other date setters too, not just DTSTART. */
    public function testOtherDateSettersAcceptDateTime(): void
    {
        $todo = new VTodo();
        $todo->setDue(new \DateTimeImmutable('2024-03-03 09:00:00', new \DateTimeZone('UTC')));
        $todo->setCompleted(new \DateTimeImmutable('2024-03-04 17:00:00', new \DateTimeZone('UTC')));

        $this->assertSame('20240303T090000Z', $todo->getProperty('DUE')?->getValue()->getRawValue());
        $this->assertSame('20240304T170000Z', $todo->getProperty('COMPLETED')?->getValue()->getRawValue());

        $event = new VEvent();
        $event->setDtEnd(new \DateTimeImmutable('2024-01-01 11:00:00', new \DateTimeZone('UTC')));
        $this->assertSame('20240101T110000Z', $event->getProperty('DTEND')?->getValue()->getRawValue());

        $event->setDtStamp(new \DateTimeImmutable('2024-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->assertSame('20240101T000000Z', $event->getProperty('DTSTAMP')?->getValue()->getRawValue());
    }
}
