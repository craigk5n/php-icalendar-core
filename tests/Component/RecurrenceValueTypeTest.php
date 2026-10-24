<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\Available;
use Icalendar\Component\ComponentInterface;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTodo;
use Icalendar\Exception\ParseException;
use Icalendar\Parser\Parser;
use Icalendar\Recurrence\RRuleParser;
use Icalendar\Writer\PropertyWriter;
use Icalendar\Writer\Writer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * RRULE is a RECUR value and EXDATE/RDATE are date lists (RFC 5545 §3.3.10,
 * §3.8.5.1, §3.8.5.2). Their `;` and `,` are structural. The setters stored
 * them as TEXT, so the TEXT writer escaped those separators and wrote
 * `RRULE:FREQ=WEEKLY\;BYDAY=MO\,WE` -- a rule this library's own Parser
 * rejects (#49). The same happened to a comma-separated EXDATE/RDATE list.
 *
 * These tests pin the written form and the round trip back through the Parser.
 */
final class RecurrenceValueTypeTest extends TestCase
{
    private const RULE = 'FREQ=WEEKLY;BYDAY=MO,WE;COUNT=3';

    /**
     * @return array<string, array{\Closure(): (VEvent|VTodo|VJournal|Available)}>
     */
    public static function rruleComponents(): array
    {
        return [
            'VEVENT' => [static fn() => new VEvent()],
            'VTODO' => [static fn() => new VTodo()],
            'VJOURNAL' => [static fn() => new VJournal()],
            'AVAILABLE' => [static fn() => new Available()],
        ];
    }

    /**
     * @param \Closure(): (VEvent|VTodo|VJournal|Available) $make
     */
    #[DataProvider('rruleComponents')]
    public function testSetRruleWritesTheRuleUnescaped(\Closure $make): void
    {
        $component = $make();
        $component->setRrule(self::RULE);

        self::assertSame('RRULE:' . self::RULE, $this->line($component, 'RRULE'));
        self::assertSame(self::RULE, $component->getRrule());
    }

    public function testSetRruleRoundTripsThroughTheParser(): void
    {
        $event = $this->event();
        $event->setRrule(self::RULE);

        $reparsed = $this->roundTrip($event);

        // The Parser writes the rule parts in its own canonical order.
        $canonical = (new RRuleParser())->parse(self::RULE)->toString();
        self::assertSame($canonical, $reparsed->getRrule());
        self::assertCount(3, $reparsed->getOccurrencesArray());
    }

    public function testACommaSeparatedExdateListStaysAList(): void
    {
        $event = $this->event();
        $event->setRrule('FREQ=DAILY;COUNT=4');
        $event->addExdate('20260106T090000,20260107T090000');

        self::assertSame('EXDATE:20260106T090000,20260107T090000', $this->line($event, 'EXDATE'));

        $starts = array_map(
            static fn($o) => $o->getStart()->format('Ymd'),
            $this->roundTrip($event)->getOccurrencesArray()
        );
        self::assertSame(['20260105', '20260108'], $starts);
    }

    public function testACommaSeparatedRdateListStaysAList(): void
    {
        $event = $this->event();
        $event->addRdate('20260110T090000,20260111T090000');

        self::assertSame('RDATE:20260110T090000,20260111T090000', $this->line($event, 'RDATE'));
        self::assertSame(['20260110T090000,20260111T090000'], $this->roundTrip($event)->getRdates());
    }

    public function testValueParameterSelectsTheType(): void
    {
        $event = new VEvent();
        $event->addExdate('20260106,20260107', ['VALUE' => 'DATE']);
        $event->addRdate('20260110T090000Z/PT1H,20260111T090000Z/PT1H', ['VALUE' => 'PERIOD']);

        self::assertSame('DATE', $event->getProperty('EXDATE')?->getValue()->getType());
        self::assertSame('PERIOD', $event->getProperty('RDATE')?->getValue()->getType());
        self::assertSame('EXDATE;VALUE=DATE:20260106,20260107', $this->line($event, 'EXDATE'));
        self::assertSame(
            'RDATE;VALUE=PERIOD:20260110T090000Z/PT1H,20260111T090000Z/PT1H',
            $this->line($event, 'RDATE')
        );
    }

    public function testSetExdateAndSetRdateUseTheSameTypes(): void
    {
        $event = new VEvent();
        $event->setExdate('20260106T090000,20260107T090000');
        $event->setRdate('20260110T090000,20260111T090000');

        self::assertSame('EXDATE:20260106T090000,20260107T090000', $this->line($event, 'EXDATE'));
        self::assertSame('RDATE:20260110T090000,20260111T090000', $this->line($event, 'RDATE'));
    }

    public function testAValueTypeThePropertyDoesNotPermitIsRejected(): void
    {
        // The Parser refuses EXDATE;VALUE=TEXT, so a setter must not write it.
        $this->expectException(ParseException::class);
        $this->expectExceptionCode(0);

        try {
            (new VEvent())->addExdate('20260106', ['VALUE' => 'TEXT']);
        } catch (ParseException $e) {
            self::assertSame(ParseException::ERR_TYPE_DECLARATION_MISMATCH, $e->getErrorCode());
            throw $e;
        }
    }

    public function testTheParserReadsACommaSeparatedExdateList(): void
    {
        $event = $this->parseEvent(
            "DTSTART:20260105T090000Z\r\nRRULE:FREQ=DAILY;COUNT=4\r\n"
            . "EXDATE:20260106T090000Z,20260107T090000Z\r\n"
        );

        self::assertSame(['20260106T090000Z,20260107T090000Z'], $event->getExdates());
        $starts = array_map(
            static fn($o) => $o->getStart()->format('Ymd'),
            $event->getOccurrencesArray()
        );
        self::assertSame(['20260105', '20260108'], $starts);
    }

    public function testTheParserKeepsEachItemsOwnUtcMarker(): void
    {
        // UTC-ness is read per item: a floating date after a UTC one stays floating.
        $event = $this->parseEvent(
            "DTSTART:20260105T090000\r\nEXDATE:20260106T090000Z,20260107T090000\r\n"
        );

        self::assertSame(['20260106T090000Z,20260107T090000'], $event->getExdates());
    }

    public function testTheParserReadsAValueDateList(): void
    {
        $event = $this->parseEvent(
            "DTSTART;VALUE=DATE:20260105\r\nRDATE;VALUE=DATE:20260110,20260111\r\n"
        );

        self::assertSame(['20260110,20260111'], $event->getRdates());
    }

    public function testABadItemInAListIsStillAnError(): void
    {
        $this->expectException(ParseException::class);

        $this->parseEvent("DTSTART:20260105T090000\r\nEXDATE:20260106T090000,not-a-date\r\n");
    }

    private function parseEvent(string $properties): VEvent
    {
        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//test//test//EN\r\n"
            . "BEGIN:VEVENT\r\nUID:list@example.com\r\nDTSTAMP:20260101T000000Z\r\n"
            . $properties
            . "END:VEVENT\r\nEND:VCALENDAR\r\n";

        $events = (new Parser())->parse($ics)->getComponents('VEVENT');
        self::assertCount(1, $events);
        self::assertInstanceOf(VEvent::class, $events[0]);

        return $events[0];
    }

    private function event(): VEvent
    {
        $event = new VEvent();
        $event->setUid('rrule@example.com')
            ->setDtStamp('20260101T000000Z')
            ->setDtStart('20260105T090000')
            ->setSummary('s');

        return $event;
    }

    private function roundTrip(VEvent $event): VEvent
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//test//test//EN')->setVersion('2.0');
        $calendar->addComponent($event);

        $events = (new Parser())->parse((new Writer())->write($calendar))->getComponents('VEVENT');
        self::assertCount(1, $events);
        self::assertInstanceOf(VEvent::class, $events[0]);

        return $events[0];
    }

    private function line(ComponentInterface $component, string $name): string
    {
        $property = $component->getProperty($name);
        self::assertNotNull($property);

        return (new PropertyWriter())->write($property);
    }
}
