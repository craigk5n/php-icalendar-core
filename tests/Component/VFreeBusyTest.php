<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VFreeBusy;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VFreeBusy component
 */
class VFreeBusyTest extends TestCase
{
    public function testCreateVFreeBusy(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertEquals('VFREEBUSY', $freebusy->getName());
    }

    public function testSetAndGetDtStamp(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setDtStamp('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $freebusy->getDtStamp());
    }

    public function testSetAndGetUid(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setUid('freebusy-12345@example.com');

        $this->assertEquals('freebusy-12345@example.com', $freebusy->getUid());
    }

    public function testSetAndGetDtStart(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setDtStart('20240215T000000Z');

        $this->assertEquals('20240215T000000Z', $freebusy->getDtStart());
    }

    public function testSetAndGetDtEnd(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setDtEnd('20240215T235959Z');

        $this->assertEquals('20240215T235959Z', $freebusy->getDtEnd());
    }

    public function testSetAndGetContact(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setContact('John Doe');

        $this->assertEquals('John Doe', $freebusy->getContact());
    }

    public function testSetAndGetOrganizer(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setOrganizer('mailto:organizer@example.com');

        $this->assertEquals('mailto:organizer@example.com', $freebusy->getOrganizer());
    }

    public function testSetAndGetAttendee(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setAttendee('mailto:attendee@example.com');

        $this->assertEquals('mailto:attendee@example.com', $freebusy->getAttendee());
    }

    public function testAddFreeBusyWithDefaultType(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z');

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertCount(1, $entries);
        $this->assertEquals('20240215T090000Z/20240215T100000Z', $entries[0]['periods']);
        $this->assertEquals('BUSY', $entries[0]['fbtype']);
    }

    public function testAddFreeBusyWithFbtypeFree(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z', VFreeBusy::FBTYPE_FREE);

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertCount(1, $entries);
        $this->assertEquals('FREE', $entries[0]['fbtype']);
    }

    public function testAddFreeBusyWithFbtypeBusy(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z', VFreeBusy::FBTYPE_BUSY);

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertEquals('BUSY', $entries[0]['fbtype']);
    }

    public function testAddFreeBusyWithFbtypeBusyUnavailable(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z', VFreeBusy::FBTYPE_BUSY_UNAVAILABLE);

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertEquals('BUSY-UNAVAILABLE', $entries[0]['fbtype']);
    }

    public function testAddFreeBusyWithFbtypeBusyTentative(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z', VFreeBusy::FBTYPE_BUSY_TENTATIVE);

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertEquals('BUSY-TENTATIVE', $entries[0]['fbtype']);
    }

    public function testAddFreeBusyWithInvalidFbtype(): void
    {
        $freebusy = new VFreeBusy();

        try {
            $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z', 'INVALID');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VFB-VAL-002', $e->getErrorCode());
            $this->assertStringContainsString('FBTYPE', $e->getMessage());
        }
    }

    public function testAddFreeBusyWithMultiplePeriods(): void
    {
        $freebusy = new VFreeBusy();
        $periods = '20240215T090000Z/20240215T100000Z,20240215T140000Z/20240215T150000Z';
        $freebusy->addFreeBusy($periods);

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertCount(1, $entries);
        $this->assertEquals($periods, $entries[0]['periods']);
    }

    public function testAddMultipleFreeBusyEntries(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z', VFreeBusy::FBTYPE_BUSY);
        $freebusy->addFreeBusy('20240215T120000Z/20240215T130000Z', VFreeBusy::FBTYPE_BUSY_TENTATIVE);
        $freebusy->addFreeBusy('20240215T140000Z/20240215T170000Z', VFreeBusy::FBTYPE_FREE);

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertCount(3, $entries);
    }

    public function testGetFreeBusyByType(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z', VFreeBusy::FBTYPE_BUSY);
        $freebusy->addFreeBusy('20240215T120000Z/20240215T130000Z', VFreeBusy::FBTYPE_BUSY_TENTATIVE);
        $freebusy->addFreeBusy('20240215T140000Z/20240215T150000Z', VFreeBusy::FBTYPE_BUSY);

        $busyEntries = $freebusy->getFreeBusyByType(VFreeBusy::FBTYPE_BUSY);
        $this->assertCount(2, $busyEntries);

        $tentativeEntries = $freebusy->getFreeBusyByType(VFreeBusy::FBTYPE_BUSY_TENTATIVE);
        $this->assertCount(1, $tentativeEntries);
    }

    public function testClearFreeBusy(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/20240215T100000Z');
        $freebusy->addFreeBusy('20240215T120000Z/20240215T130000Z');
        $this->assertCount(2, $freebusy->getFreeBusyEntries());

        $freebusy->clearFreeBusy();
        $this->assertEmpty($freebusy->getFreeBusyEntries());
    }

    public function testAddFreeBusyWithDuration(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000Z/PT1H');

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertCount(1, $entries);
        $this->assertEquals('20240215T090000Z/PT1H', $entries[0]['periods']);
    }

    public function testAddFreeBusyWithInvalidPeriod(): void
    {
        $freebusy = new VFreeBusy();

        try {
            $freebusy->addFreeBusy('invalid-period');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VFB-VAL-001', $e->getErrorCode());
            $this->assertStringContainsString('PERIOD', $e->getMessage());
        }
    }

    public function testAddFreeBusyWithInvalidPeriodMissingSlash(): void
    {
        $freebusy = new VFreeBusy();

        try {
            $freebusy->addFreeBusy('20240215T090000Z');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VFB-VAL-001', $e->getErrorCode());
        }
    }

    public function testValidateMissingDtStamp(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setUid('freebusy-12345@example.com');

        try {
            $freebusy->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VFB-001', $e->getErrorCode());
            $this->assertStringContainsString('DTSTAMP', $e->getMessage());
        }
    }

    public function testValidateMissingUid(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setDtStamp('20240215T120000Z');

        try {
            $freebusy->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VFB-002', $e->getErrorCode());
            $this->assertStringContainsString('UID', $e->getMessage());
        }
    }

    public function testValidateSuccess(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setDtStamp('20240215T120000Z');
        $freebusy->setUid('freebusy-12345@example.com');

        $this->assertNull($freebusy->validate());
    }

    public function testFluentInterface(): void
    {
        $freebusy = new VFreeBusy();

        $result = $freebusy->setDtStamp('20240215T120000Z')
            ->setUid('freebusy-12345@example.com')
            ->setDtStart('20240215T000000Z')
            ->setDtEnd('20240215T235959Z')
            ->setOrganizer('mailto:organizer@example.com')
            ->setContact('John Doe')
            ->addFreeBusy('20240215T090000Z/20240215T100000Z', VFreeBusy::FBTYPE_BUSY);

        $this->assertSame($freebusy, $result);
        $this->assertEquals('20240215T120000Z', $freebusy->getDtStamp());
        $this->assertEquals('freebusy-12345@example.com', $freebusy->getUid());
        $this->assertEquals('20240215T000000Z', $freebusy->getDtStart());
        $this->assertEquals('20240215T235959Z', $freebusy->getDtEnd());
        $this->assertEquals('mailto:organizer@example.com', $freebusy->getOrganizer());
        $this->assertEquals('John Doe', $freebusy->getContact());
        $this->assertCount(1, $freebusy->getFreeBusyEntries());
    }

    public function testClearFreeBusyFluentInterface(): void
    {
        $freebusy = new VFreeBusy();

        $result = $freebusy->clearFreeBusy();

        $this->assertSame($freebusy, $result);
    }

    public function testGetDtStampWhenNotSet(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertNull($freebusy->getDtStamp());
    }

    public function testGetUidWhenNotSet(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertNull($freebusy->getUid());
    }

    public function testGetDtStartWhenNotSet(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertNull($freebusy->getDtStart());
    }

    public function testGetDtEndWhenNotSet(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertNull($freebusy->getDtEnd());
    }

    public function testGetContactWhenNotSet(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertNull($freebusy->getContact());
    }

    public function testGetOrganizerWhenNotSet(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertNull($freebusy->getOrganizer());
    }

    public function testGetAttendeeWhenNotSet(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertNull($freebusy->getAttendee());
    }

    public function testGetFreeBusyEntriesWhenEmpty(): void
    {
        $freebusy = new VFreeBusy();

        $this->assertEmpty($freebusy->getFreeBusyEntries());
    }

    public function testOverwriteDtStamp(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setDtStamp('20240215T120000Z');
        $freebusy->setDtStamp('20240216T120000Z');

        $this->assertEquals('20240216T120000Z', $freebusy->getDtStamp());
    }

    public function testOverwriteUid(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setUid('freebusy-1@example.com');
        $freebusy->setUid('freebusy-2@example.com');

        $this->assertEquals('freebusy-2@example.com', $freebusy->getUid());
    }

    public function testParentIsSetWhenAddedToCalendar(): void
    {
        $calendar = new \Icalendar\Component\VCalendar();
        $freebusy = new VFreeBusy();
        $freebusy->setDtStamp('20240215T120000Z');
        $freebusy->setUid('freebusy-12345@example.com');

        $calendar->addComponent($freebusy);

        $this->assertNull($calendar->getParent());
        $this->assertSame($calendar, $freebusy->getParent());
    }

    public function testFreeBusyWithLocalTime(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->addFreeBusy('20240215T090000/20240215T100000');

        $entries = $freebusy->getFreeBusyEntries();
        $this->assertCount(1, $entries);
    }

    public function testCompleteFreeBusyRequest(): void
    {
        $freebusy = new VFreeBusy();
        $freebusy->setDtStamp('20240215T120000Z')
            ->setUid('freebusy-request-1@example.com')
            ->setDtStart('20240301T000000Z')
            ->setDtEnd('20240331T235959Z')
            ->setOrganizer('mailto:boss@example.com')
            ->setAttendee('mailto:employee@example.com')
            ->addFreeBusy('20240305T090000Z/20240305T170000Z', VFreeBusy::FBTYPE_BUSY)
            ->addFreeBusy('20240310T090000Z/20240310T120000Z', VFreeBusy::FBTYPE_BUSY_TENTATIVE)
            ->addFreeBusy('20240315T000000Z/20240315T235959Z', VFreeBusy::FBTYPE_BUSY_UNAVAILABLE);

        $this->assertNull($freebusy->validate());
        $this->assertCount(3, $freebusy->getFreeBusyEntries());

        $busyEntries = $freebusy->getFreeBusyByType(VFreeBusy::FBTYPE_BUSY);
        $this->assertCount(1, $busyEntries);
    }
}
