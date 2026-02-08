<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Parser\Parser;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RFC 7986 properties: IMAGE, COLOR, CONFERENCE, REFRESH-INTERVAL
 */
class Rfc7986Test extends TestCase
{
    private Parser $parser;

    #[\Override]
    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseRfc7986Properties(): void
    {
        $icalData = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
REFRESH-INTERVAL:PT1H
COLOR:blue
BEGIN:VEVENT
UID:test-rfc7986@example.com
DTSTAMP:20260207T100000Z
SUMMARY:Event with RFC 7986
IMAGE;VALUE=URI:http://example.com/poster.png
COLOR:red
CONFERENCE:http://zoom.us/j/12345
END:VEVENT
END:VCALENDAR
ICAL;

        $calendar = $this->parser->parse($icalData);
        
        // Check VCALENDAR properties
        $this->assertEquals('PT1H', $calendar->getRefreshInterval());
        $this->assertEquals('blue', $calendar->getColor());

        // Check VEVENT properties
        $events = $calendar->getComponents('VEVENT');
        $this->assertCount(1, $events);
        /** @var VEvent $event */
        $event = $events[0];
        
        $this->assertEquals('http://example.com/poster.png', $event->getImage());
        $this->assertEquals('red', $event->getColor());
        $this->assertEquals('http://zoom.us/j/12345', $event->getConference());
    }

    public function testSetAndGetRfc7986Properties(): void
    {
        $calendar = new VCalendar();
        $calendar->setRefreshInterval('PT15M');
        $calendar->setColor('silver');

        $event = new VEvent();
        $event->setUid('manual-test@example.com');
        $event->setImage('http://example.com/manual.jpg');
        $event->setColor('green');
        $event->setConference('https://teams.microsoft.com/l/meetup-join/123');
        
        $calendar->addComponent($event);

        $this->assertEquals('PT15M', $calendar->getRefreshInterval());
        $this->assertEquals('silver', $calendar->getColor());
        $this->assertEquals('http://example.com/manual.jpg', $event->getImage());
        $this->assertEquals('green', $event->getColor());
        $this->assertEquals('https://teams.microsoft.com/l/meetup-join/123', $event->getConference());
    }
}
