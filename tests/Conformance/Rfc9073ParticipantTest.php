<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Parser\Parser;
use Icalendar\Component\Participant;
use Icalendar\Component\VEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RFC 9073: PARTICIPANT support
 */
class Rfc9073ParticipantTest extends TestCase
{
    private Parser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseParticipant(): void
    {
        $icalData = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
BEGIN:VEVENT
UID:event-with-participant@example.com
DTSTAMP:20260207T100000Z
BEGIN:PARTICIPANT
PARTICIPANT-TYPE:CHAIR
CAL-ADDRESS:mailto:chair@example.com
END:PARTICIPANT
END:VEVENT
END:VCALENDAR
ICAL;

        $calendar = $this->parser->parse($icalData);
        $event = $calendar->getComponents('VEVENT')[0];
        
        $participants = $event->getComponents('PARTICIPANT');
        $this->assertCount(1, $participants);
        
        /** @var Participant $participant */
        $participant = $participants[0];
        $this->assertEquals('CHAIR', $participant->getParticipantType());
        $this->assertEquals('mailto:chair@example.com', $participant->getCalendarAddress());
    }
}
