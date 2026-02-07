<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Parser\Parser;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for common de-facto extensions
 */
class CommonExtensionsTest extends TestCase
{
    public function testCommonExtensions(): void
    {
        $calendar = new VCalendar();
        $calendar->setCalendarName('My Work Calendar');
        $calendar->setCalendarTimezone('Europe/London');

        $event = new VEvent();
        $event->setUid('event-1');
        $event->setAppleStructuredLocation('geo:51.5074,-0.1278', ['VALUE' => 'URI']);
        $calendar->addComponent($event);

        $this->assertEquals('My Work Calendar', $calendar->getCalendarName());
        $this->assertEquals('Europe/London', $calendar->getCalendarTimezone());
        $this->assertEquals('geo:51.5074,-0.1278', $event->getAppleStructuredLocation());
    }

    public function testParseCommonExtensions(): void
    {
        $icalData = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
X-WR-CALNAME:Parsed Calendar
X-WR-TIMEZONE:America/New_York
BEGIN:VEVENT
UID:e1
DTSTAMP:20260207T100000Z
X-APPLE-STRUCTURED-LOCATION;VALUE=URI:geo:40.7128,-74.0060
END:VEVENT
END:VCALENDAR
ICAL;

        $parser = new Parser();
        $calendar = $parser->parse($icalData);

        $this->assertEquals('Parsed Calendar', $calendar->getCalendarName());
        $this->assertEquals('America/New_York', $calendar->getCalendarTimezone());
        
        $event = $calendar->getComponents('VEVENT')[0];
        $this->assertEquals('geo:40.7128,-74.0060', $event->getAppleStructuredLocation());
    }
}
