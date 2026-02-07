<?php

declare(strict_types=1);

namespace Icalendar\Tests\Conformance;

use Icalendar\Parser\Parser;
use Icalendar\Component\VCalendar;
use Icalendar\Component\VEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RFC 7265: jCal (JSON Format for iCalendar)
 */
class Rfc7265Test extends TestCase
{
    public function testTojCalArray(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//EN');
        $calendar->setVersion('2.0');

        $event = new VEvent();
        $event->setUid('event-1@example.com');
        $event->setSummary('jCal Test');
        $calendar->addComponent($event);

        $array = $calendar->toArray();

        $this->assertEquals('vcalendar', $array[0]);
        $this->assertIsArray($array[1]); // properties
        $this->assertIsArray($array[2]); // components

        $foundProdid = false;
        foreach ($array[1] as $prop) {
            if ($prop[0] === 'prodid') {
                $foundProdid = true;
                $this->assertEquals('text', $prop[2]);
                $this->assertEquals('-//Test//EN', $prop[3]);
            }
        }
        $this->assertTrue($foundProdid);

        $this->assertCount(1, $array[2]);
        $this->assertEquals('vevent', $array[2][0][0]);
    }

    public function testToJson(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//Test//EN');
        $calendar->setVersion('2.0');

        $json = $calendar->toJson();
        $this->assertJson($json);
        $this->assertStringContainsString('vcalendar', $json);
        $this->assertStringContainsString('prodid', $json);
    }
}
