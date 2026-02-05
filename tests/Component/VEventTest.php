<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use DateInterval;
use DateTimeImmutable;
use Icalendar\Component\VEvent;
use Icalendar\Component\VAlarm;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VEvent component
 */
class VEventTest extends TestCase
{
    public function testCreateVEvent(): void
    {
        $event = new VEvent();

        $this->assertEquals('VEVENT', $event->getName());
    }

    public function testSetAndGetDtStamp(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $event->getDtStamp());
    }

    public function testSetAndGetUid(): void
    {
        $event = new VEvent();
        $event->setUid('event-12345@example.com');

        $this->assertEquals('event-12345@example.com', $event->getUid());
    }

    public function testSetAndGetDtStart(): void
    {
        $event = new VEvent();
        $event->setDtStart('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $event->getDtStart());
    }

    public function testSetAndGetDtEnd(): void
    {
        $event = new VEvent();
        $event->setDtEnd('20240215T140000Z');

        $this->assertEquals('20240215T140000Z', $event->getDtEnd());
    }

    public function testSetAndGetDuration(): void
    {
        $event = new VEvent();
        $event->setDuration('PT2H');

        $this->assertEquals('PT2H', $event->getDuration());
    }

    public function testSetAndGetRrule(): void
    {
        $event = new VEvent();
        $event->setRrule('FREQ=DAILY;COUNT=5');

        $this->assertEquals('FREQ=DAILY;COUNT=5', $event->getRrule());
    }

    public function testSetAndGetSummary(): void
    {
        $event = new VEvent();
        $event->setSummary('Team Meeting');

        $this->assertEquals('Team Meeting', $event->getSummary());
    }

    public function testSetAndGetDescription(): void
    {
        $event = new VEvent();
        $event->setDescription('Discuss project updates');

        $this->assertEquals('Discuss project updates', $event->getDescription());
    }

    public function testSetAndGetLocation(): void
    {
        $event = new VEvent();
        $event->setLocation('Conference Room A');

        $this->assertEquals('Conference Room A', $event->getLocation());
    }

    public function testSetAndGetStatus(): void
    {
        $event = new VEvent();
        $event->setStatus(VEvent::STATUS_CONFIRMED);

        $this->assertEquals('CONFIRMED', $event->getStatus());
    }

    public function testSetInvalidStatus(): void
    {
        $event = new VEvent();

        try {
            $event->setStatus('INVALID_STATUS');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VEVENT-VAL-003', $e->getErrorCode());
            $this->assertStringContainsString('Invalid VEVENT status', $e->getMessage());
        }
    }

    public function testSetAndGetCategories(): void
    {
        $event = new VEvent();
        $event->setCategories('meeting', 'work');

        $this->assertEquals(['meeting', 'work'], $event->getCategories());
    }

    public function testSetAndGetUrl(): void
    {
        $event = new VEvent();
        $event->setUrl('https://example.com/event');

        $this->assertEquals('https://example.com/event', $event->getUrl());
    }

    public function testSetAndGetGeo(): void
    {
        $event = new VEvent();
        $event->setGeo(37.7749, -122.4194);

        $geo = $event->getGeo();
        $this->assertNotNull($geo);
        $this->assertEquals(37.7749, $geo['latitude']);
        $this->assertEquals(-122.4194, $geo['longitude']);
    }

    public function testAddAndGetAlarms(): void
    {
        $event = new VEvent();
        $alarm1 = new VAlarm();
        $alarm1->setTrigger('-PT15M');

        $alarm2 = new VAlarm();
        $alarm2->setTrigger('-PT1H');

        $event->addAlarm($alarm1);
        $event->addAlarm($alarm2);

        $alarms = $event->getAlarms();
        $this->assertCount(2, $alarms);
    }

    public function testGetAlarmReturnsEmptyArray(): void
    {
        $event = new VEvent();

        $this->assertEmpty($event->getAlarms());
    }

    public function testValidateMissingDtStamp(): void
    {
        $event = new VEvent();
        $event->setUid('event-12345@example.com');

        try {
            $event->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VEVENT-001', $e->getErrorCode());
            $this->assertStringContainsString('DTSTAMP', $e->getMessage());
        }
    }

    public function testValidateMissingUid(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');

        try {
            $event->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VEVENT-002', $e->getErrorCode());
            $this->assertStringContainsString('UID', $e->getMessage());
        }
    }

    public function testValidateBothDtEndAndDuration(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');
        $event->setUid('event-12345@example.com');
        $event->setDtEnd('20240215T140000Z');
        $event->setDuration('PT2H');

        try {
            $event->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VEVENT-VAL-001', $e->getErrorCode());
            $this->assertStringContainsString('DTEND', $e->getMessage());
            $this->assertStringContainsString('DURATION', $e->getMessage());
        }
    }

    public function testValidateSuccess(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');
        $event->setUid('event-12345@example.com');

        $this->assertNull($event->validate());
    }

    public function testValidateSuccessWithDtEnd(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');
        $event->setUid('event-12345@example.com');
        $event->setDtEnd('20240215T140000Z');

        $this->assertNull($event->validate());
    }

    public function testValidateSuccessWithDuration(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');
        $event->setUid('event-12345@example.com');
        $event->setDuration('PT2H');

        $this->assertNull($event->validate());
    }

    public function testOverwriteDtStamp(): void
    {
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');
        $event->setDtStamp('20240216T120000Z');

        $this->assertEquals('20240216T120000Z', $event->getDtStamp());
        $this->assertCount(1, $event->getProperties());
    }

    public function testOverwriteUid(): void
    {
        $event = new VEvent();
        $event->setUid('event-1@example.com');
        $event->setUid('event-2@example.com');

        $this->assertEquals('event-2@example.com', $event->getUid());
        $this->assertCount(1, $event->getProperties());
    }

    public function testGetDtStampWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getDtStamp());
    }

    public function testGetUidWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getUid());
    }

    public function testGetDtStartWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getDtStart());
    }

    public function testGetDtEndWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getDtEnd());
    }

    public function testGetDurationWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getDuration());
    }

    public function testGetRruleWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getRrule());
    }

    public function testGetSummaryWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getSummary());
    }

    public function testGetDescriptionWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getDescription());
    }

    public function testGetLocationWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getLocation());
    }

    public function testGetStatusWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getStatus());
    }

    public function testGetCategoriesWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertEmpty($event->getCategories());
    }

    public function testGetUrlWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getUrl());
    }

    public function testGetGeoWhenNotSet(): void
    {
        $event = new VEvent();

        $this->assertNull($event->getGeo());
    }

    public function testGetGeoWithInvalidFormat(): void
    {
        $event = new VEvent();
        $prop = new \Icalendar\Property\GenericProperty('GEO', new \Icalendar\Value\TextValue('invalid'));
        $event->addProperty($prop);

        $this->assertNull($event->getGeo());
    }

    public function testAddAlarmWithFluentInterface(): void
    {
        $event = new VEvent();
        $alarm = new VAlarm();

        $result = $event->addAlarm($alarm);

        $this->assertSame($event, $result);
        $this->assertCount(1, $event->getAlarms());
    }

    public function testAllStatusValues(): void
    {
        $event = new VEvent();

        $event->setStatus(VEvent::STATUS_TENTATIVE);
        $this->assertEquals('TENTATIVE', $event->getStatus());

        $event->setStatus(VEvent::STATUS_CONFIRMED);
        $this->assertEquals('CONFIRMED', $event->getStatus());

        $event->setStatus(VEvent::STATUS_CANCELLED);
        $this->assertEquals('CANCELLED', $event->getStatus());
    }

    public function testDurationFormat(): void
    {
        $event = new VEvent();
        $event->setDuration('P1DT2H30M');

        $prop = $event->getProperty('DURATION');
        $this->assertNotNull($prop);
        $this->assertEquals('P1DT2H30M', $prop->getValue()->getRawValue());
    }

    public function testDurationFormatWeeksAndDays(): void
    {
        $event = new VEvent();
        $event->setDuration('P2W3D');

        $prop = $event->getProperty('DURATION');
        $this->assertNotNull($prop);
        $this->assertEquals('P2W3D', $prop->getValue()->getRawValue());
    }

    public function testDurationFormatOnlySeconds(): void
    {
        $event = new VEvent();
        $event->setDuration('PT30S');

        $prop = $event->getProperty('DURATION');
        $this->assertNotNull($prop);
        $this->assertEquals('PT30S', $prop->getValue()->getRawValue());
    }

    public function testFluentInterface(): void
    {
        $event = new VEvent();

        $result = $event->setDtStamp('20240215T120000Z')
            ->setUid('event-12345@example.com')
            ->setSummary('Team Meeting')
            ->setStatus(VEvent::STATUS_CONFIRMED);

        $this->assertSame($event, $result);
        $this->assertEquals('20240215T120000Z', $event->getDtStamp());
        $this->assertEquals('event-12345@example.com', $event->getUid());
        $this->assertEquals('Team Meeting', $event->getSummary());
        $this->assertEquals('CONFIRMED', $event->getStatus());
    }

    public function testParentIsSetWhenAddedToCalendar(): void
    {
        $calendar = new \Icalendar\Component\VCalendar();
        $event = new VEvent();
        $event->setDtStamp('20240215T120000Z');
        $event->setUid('event-12345@example.com');

        $calendar->addComponent($event);

        $this->assertNull($calendar->getParent());
        $this->assertSame($calendar, $event->getParent());
    }

    public function testRemoveAlarm(): void
    {
        $event = new VEvent();
        $alarm1 = new VAlarm();
        $alarm1->setTrigger('-PT15M');

        $alarm2 = new VAlarm();
        $alarm2->setTrigger('-PT1H');

        $event->addAlarm($alarm1);
        $event->addAlarm($alarm2);
        $this->assertCount(2, $event->getAlarms());

        $event->removeComponent($alarm1);
        $this->assertCount(1, $event->getAlarms());
    }

    public function testCategoriesSingleValue(): void
    {
        $event = new VEvent();
        $event->setCategories('important');

        $this->assertEquals(['important'], $event->getCategories());
    }

    public function testCategoriesEmpty(): void
    {
        $event = new VEvent();
        $event->setCategories('');

        $this->assertEquals([], $event->getCategories());
    }
}
