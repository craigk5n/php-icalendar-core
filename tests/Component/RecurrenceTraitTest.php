<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use DateTimeImmutable;
use Icalendar\Component\VEvent;
use Icalendar\Component\VJournal;
use Icalendar\Component\VTodo;
use Icalendar\Recurrence\Occurrence;
use PHPUnit\Framework\TestCase;

class RecurrenceTraitTest extends TestCase
{
    public function testAddExdateAccumulates(): void
    {
        $event = new VEvent();
        $event->addExdate('20260101T090000');
        $event->addExdate('20260102T090000');

        $exdates = $event->getExdates();
        $this->assertCount(2, $exdates);
        $this->assertEquals('20260101T090000', $exdates[0]);
        $this->assertEquals('20260102T090000', $exdates[1]);
    }

    public function testSetExdateReplacesAll(): void
    {
        $event = new VEvent();
        $event->addExdate('20260101T090000');
        $event->addExdate('20260102T090000');
        
        $event->setExdate('20260103T090000');

        $exdates = $event->getExdates();
        $this->assertCount(1, $exdates);
        $this->assertEquals('20260103T090000', $exdates[0]);
    }

    public function testGetExdatesEmpty(): void
    {
        $event = new VEvent();
        $this->assertEmpty($event->getExdates());
    }

    public function testAddRdateAccumulates(): void
    {
        $event = new VEvent();
        $event->addRdate('20260101T090000');
        $event->addRdate('20260102T090000');

        $rdates = $event->getRdates();
        $this->assertCount(2, $rdates);
        $this->assertEquals('20260101T090000', $rdates[0]);
        $this->assertEquals('20260102T090000', $rdates[1]);
    }

    public function testSetRdateReplacesAll(): void
    {
        $event = new VEvent();
        $event->addRdate('20260101T090000');
        $event->addRdate('20260102T090000');
        
        $event->setRdate('20260103T090000');

        $rdates = $event->getRdates();
        $this->assertCount(1, $rdates);
        $this->assertEquals('20260103T090000', $rdates[0]);
    }

    public function testGetRdatesEmpty(): void
    {
        $event = new VEvent();
        $this->assertEmpty($event->getRdates());
    }

    public function testExdateWithParameters(): void
    {
        $event = new VEvent();
        $event->addExdate('20260115', ['VALUE' => 'DATE']);

        $props = $event->getAllProperties('EXDATE');
        $this->assertCount(1, $props);
        $this->assertEquals('DATE', $props[0]->getParameter('VALUE'));
    }

    public function testVtodoSetRruleGetRrule(): void
    {
        $todo = new VTodo();
        $todo->setRrule('FREQ=DAILY;COUNT=3');
        $this->assertEquals('FREQ=DAILY;COUNT=3', $todo->getRrule());
    }

    public function testVtodoGetRruleReturnsNullWhenNotSet(): void
    {
        $todo = new VTodo();
        $this->assertNull($todo->getRrule());
    }

    public function testGetOccurrencesIntegration(): void
    {
        $event = new VEvent();
        $event->setDtStart('20260101T090000');
        $event->setRrule('FREQ=DAILY;COUNT=3');
        $event->addExdate('20260102T090000');

        $occurrences = iterator_to_array($event->getOccurrences());

        $this->assertCount(2, $occurrences);
        $this->assertEquals('2026-01-01', $occurrences[0]->getStart()->format('Y-m-d'));
        $this->assertEquals('2026-01-03', $occurrences[1]->getStart()->format('Y-m-d'));
    }

    public function testGetOccurrencesArrayReturnsArray(): void
    {
        $event = new VEvent();
        $event->setDtStart('20260101T090000');
        $event->setRrule('FREQ=DAILY;COUNT=3');

        $occurrences = $event->getOccurrencesArray();

        $this->assertIsArray($occurrences);
        $this->assertCount(3, $occurrences);
        $this->assertInstanceOf(Occurrence::class, $occurrences[0]);
    }

    public function testVtodoGetOccurrencesWithDue(): void
    {
        $todo = new VTodo();
        $todo->setDtStart('20260101T090000');
        $todo->setDue('20260101T103000');
        $todo->setRrule('FREQ=DAILY;COUNT=3');

        $occurrences = $todo->getOccurrencesArray();

        $this->assertCount(3, $occurrences);
        foreach ($occurrences as $occ) {
            $this->assertEquals(5400, $occ->getEnd()->getTimestamp() - $occ->getStart()->getTimestamp());
        }
    }

    public function testVjournalGetOccurrencesEndIsNull(): void
    {
        $journal = new VJournal();
        $journal->setDtStart('20260101T090000');
        $journal->setRrule('FREQ=DAILY;COUNT=3');

        $occurrences = $journal->getOccurrencesArray();

        $this->assertCount(3, $occurrences);
        foreach ($occurrences as $occ) {
            $this->assertNull($occ->getEnd());
        }
    }
}
