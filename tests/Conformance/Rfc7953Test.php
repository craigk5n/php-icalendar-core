<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Parser\Parser;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VAvailability;
use Icalendar\Component\Available;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RFC 7953: Calendar Availability (VAVAILABILITY)
 */
class Rfc7953Test extends TestCase
{
    private Parser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseVAvailability(): void
    {
        $icalData = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
BEGIN:VAVAILABILITY
UID:avail-123@example.com
DTSTAMP:20260207T100000Z
BUSYTYPE:BUSY
BEGIN:AVAILABLE
UID:slot-1@example.com
DTSTAMP:20260207T100000Z
DTSTART:20260209T090000Z
DTEND:20260209T170000Z
RRULE:FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR
ICAL;

        $calendar = $this->parser->parse($icalData);
        $vavailabilities = $calendar->getComponents('VAVAILABILITY');
        
        $this->assertCount(1, $vavailabilities);
        /** @var VAvailability $vavail */
        $vavail = $vavailabilities[0];
        
        $this->assertEquals('avail-123@example.com', $vavail->getUid());
        $this->assertEquals('BUSY', $vavail->getBusyType());
        
        $availables = $vavail->getAvailable();
        $this->assertCount(1, $availables);
        
        $slot = $availables[0];
        $this->assertEquals('slot-1@example.com', $slot->getUid());
        $this->assertEquals('20260209T090000Z', $slot->getDtStart());
        $this->assertStringContainsString('FREQ=WEEKLY', $slot->getRrule() ?? '');
    }

    public function testManualVAvailability(): void
    {
        $vavail = new VAvailability();
        $vavail->setUid('manual-avail');
        $vavail->setDtStamp('20260207T100000Z');
        
        $slot = new Available();
        $slot->setUid('manual-slot');
        $slot->setDtStamp('20260207T100000Z');
        $slot->setDtStart('20260210T100000Z');
        $slot->setDuration('PT1H');
        
        $vavail->addAvailable($slot);
        
        $this->assertCount(1, $vavail->getAvailable());
        $this->assertEquals('manual-avail', $vavail->getUid());
        $this->assertEquals('PT1H', $vavail->getAvailable()[0]->getDuration());
    }
}
