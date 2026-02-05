<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VCalendar;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VCalendar component
 */
class VCalendarTest extends TestCase
{
    public function testCreateVCalendar(): void
    {
        $calendar = new VCalendar();

        $this->assertEquals('VCALENDAR', $calendar->getName());
    }

    public function testSetAndGetProductId(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//MyApp//MyCalendar//EN');

        $this->assertEquals('-//MyApp//MyCalendar//EN', $calendar->getProductId());
    }

    public function testSetAndGetVersion(): void
    {
        $calendar = new VCalendar();
        $calendar->setVersion('2.0');

        $this->assertEquals('2.0', $calendar->getVersion());
    }

    public function testSetAndGetCalscale(): void
    {
        $calendar = new VCalendar();
        $calendar->setCalscale('GREGORIAN');

        $this->assertEquals('GREGORIAN', $calendar->getCalscale());
    }

    public function testSetAndGetMethod(): void
    {
        $calendar = new VCalendar();
        $calendar->setMethod('PUBLISH');

        $this->assertEquals('PUBLISH', $calendar->getMethod());
    }

    public function testGetProductIdWhenNotSet(): void
    {
        $calendar = new VCalendar();

        $this->assertNull($calendar->getProductId());
    }

    public function testGetVersionWhenNotSet(): void
    {
        $calendar = new VCalendar();

        $this->assertNull($calendar->getVersion());
    }

    public function testGetCalscaleWhenNotSet(): void
    {
        $calendar = new VCalendar();

        $this->assertNull($calendar->getCalscale());
    }

    public function testGetMethodWhenNotSet(): void
    {
        $calendar = new VCalendar();

        $this->assertNull($calendar->getMethod());
    }

    public function testValidateMissingProdId(): void
    {
        $calendar = new VCalendar();
        $calendar->setVersion('2.0');

        try {
            $calendar->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-COMP-001', $e->getErrorCode());
            $this->assertStringContainsString('PRODID', $e->getMessage());
        }
    }

    public function testValidateMissingVersion(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//MyApp//MyCalendar//EN');

        try {
            $calendar->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-COMP-002', $e->getErrorCode());
            $this->assertStringContainsString('VERSION', $e->getMessage());
        }
    }

    public function testValidateSuccess(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//MyApp//MyCalendar//EN');
        $calendar->setVersion('2.0');

        $this->assertNull($calendar->validate());
    }

    public function testValidateSuccessWithOptionalProperties(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//MyApp//MyCalendar//EN');
        $calendar->setVersion('2.0');
        $calendar->setCalscale('GREGORIAN');
        $calendar->setMethod('PUBLISH');

        $this->assertNull($calendar->validate());
    }

    public function testOverwriteProductId(): void
    {
        $calendar = new VCalendar();
        $calendar->setProductId('-//MyApp//v1.0//EN');
        $calendar->setProductId('-//MyApp//v2.0//EN');

        $this->assertEquals('-//MyApp//v2.0//EN', $calendar->getProductId());
        $this->assertCount(1, $calendar->getProperties());
    }

    public function testOverwriteVersion(): void
    {
        $calendar = new VCalendar();
        $calendar->setVersion('1.0');
        $calendar->setVersion('2.0');

        $this->assertEquals('2.0', $calendar->getVersion());
        $this->assertCount(1, $calendar->getProperties());
    }

    public function testAddAndGetComponents(): void
    {
        $calendar = new VCalendar();
        $event = new \Icalendar\Component\VEvent();

        $calendar->addComponent($event);

        $components = $calendar->getComponents();
        $this->assertCount(1, $components);
        $this->assertEquals('VEVENT', $components[0]->getName());
    }

    public function testGetComponentsByType(): void
    {
        $calendar = new VCalendar();
        $event1 = new \Icalendar\Component\VEvent();
        $event2 = new \Icalendar\Component\VEvent();
        $todo = new \Icalendar\Component\VTodo();

        $calendar->addComponent($event1);
        $calendar->addComponent($event2);
        $calendar->addComponent($todo);

        $events = $calendar->getComponents('VEVENT');
        $this->assertCount(2, $events);

        $todos = $calendar->getComponents('VTODO');
        $this->assertCount(1, $todos);
    }

    public function testRemoveComponent(): void
    {
        $calendar = new VCalendar();
        $event = new \Icalendar\Component\VEvent();

        $calendar->addComponent($event);
        $this->assertCount(1, $calendar->getComponents());

        $calendar->removeComponent($event);
        $this->assertCount(0, $calendar->getComponents());
    }

    public function testGetParent(): void
    {
        $calendar = new VCalendar();
        $event = new \Icalendar\Component\VEvent();

        $calendar->addComponent($event);

        $this->assertNull($calendar->getParent());
        $this->assertSame($calendar, $event->getParent());
    }

    public function testFluentInterface(): void
    {
        $calendar = new VCalendar();

        $result = $calendar->setProductId('-//Test//Test//EN')
            ->setVersion('2.0')
            ->setCalscale('GREGORIAN')
            ->setMethod('PUBLISH');

        $this->assertSame($calendar, $result);
        $this->assertEquals('-//Test//Test//EN', $calendar->getProductId());
        $this->assertEquals('2.0', $calendar->getVersion());
    }
}
