<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VTodo;
use Icalendar\Component\VAlarm;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VTodo component
 */
class VTodoTest extends TestCase
{
    public function testCreateVTodo(): void
    {
        $todo = new VTodo();

        $this->assertEquals('VTODO', $todo->getName());
    }

    public function testSetAndGetDtStamp(): void
    {
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $todo->getDtStamp());
    }

    public function testSetAndGetUid(): void
    {
        $todo = new VTodo();
        $todo->setUid('todo-12345@example.com');

        $this->assertEquals('todo-12345@example.com', $todo->getUid());
    }

    public function testSetAndGetDtStart(): void
    {
        $todo = new VTodo();
        $todo->setDtStart('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $todo->getDtStart());
    }

    public function testSetAndGetDue(): void
    {
        $todo = new VTodo();
        $todo->setDue('20240220T170000Z');

        $this->assertEquals('20240220T170000Z', $todo->getDue());
    }

    public function testSetAndGetCompleted(): void
    {
        $todo = new VTodo();
        $todo->setCompleted('20240218T143000Z');

        $this->assertEquals('20240218T143000Z', $todo->getCompleted());
    }

    public function testSetAndGetDuration(): void
    {
        $todo = new VTodo();
        $todo->setDuration('PT2H');

        $this->assertEquals('PT2H', $todo->getDuration());
    }

    public function testSetAndGetPercentComplete(): void
    {
        $todo = new VTodo();
        $todo->setPercentComplete(50);

        $this->assertEquals(50, $todo->getPercentComplete());
    }

    public function testSetPercentCompleteZero(): void
    {
        $todo = new VTodo();
        $todo->setPercentComplete(0);

        $this->assertEquals(0, $todo->getPercentComplete());
    }

    public function testSetPercentCompleteHundred(): void
    {
        $todo = new VTodo();
        $todo->setPercentComplete(100);

        $this->assertEquals(100, $todo->getPercentComplete());
    }

    public function testSetPercentCompleteInvalidNegative(): void
    {
        $todo = new VTodo();

        try {
            $todo->setPercentComplete(-1);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-VAL-003', $e->getErrorCode());
            $this->assertStringContainsString('PERCENT-COMPLETE', $e->getMessage());
        }
    }

    public function testSetPercentCompleteInvalidOver100(): void
    {
        $todo = new VTodo();

        try {
            $todo->setPercentComplete(101);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-VAL-003', $e->getErrorCode());
            $this->assertStringContainsString('PERCENT-COMPLETE', $e->getMessage());
        }
    }

    public function testSetAndGetPriority(): void
    {
        $todo = new VTodo();
        $todo->setPriority(1);

        $this->assertEquals(1, $todo->getPriority());
    }

    public function testSetPriorityZero(): void
    {
        $todo = new VTodo();
        $todo->setPriority(0);

        $this->assertEquals(0, $todo->getPriority());
    }

    public function testSetPriorityNine(): void
    {
        $todo = new VTodo();
        $todo->setPriority(9);

        $this->assertEquals(9, $todo->getPriority());
    }

    public function testSetPriorityInvalidNegative(): void
    {
        $todo = new VTodo();

        try {
            $todo->setPriority(-1);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-VAL-004', $e->getErrorCode());
            $this->assertStringContainsString('PRIORITY', $e->getMessage());
        }
    }

    public function testSetPriorityInvalidOverNine(): void
    {
        $todo = new VTodo();

        try {
            $todo->setPriority(10);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-VAL-004', $e->getErrorCode());
            $this->assertStringContainsString('PRIORITY', $e->getMessage());
        }
    }

    public function testSetAndGetSummary(): void
    {
        $todo = new VTodo();
        $todo->setSummary('Complete project report');

        $this->assertEquals('Complete project report', $todo->getSummary());
    }

    public function testSetAndGetDescription(): void
    {
        $todo = new VTodo();
        $todo->setDescription('Write and submit the quarterly report');

        $this->assertEquals('Write and submit the quarterly report', $todo->getDescription());
    }

    public function testSetAndGetLocation(): void
    {
        $todo = new VTodo();
        $todo->setLocation('Home Office');

        $this->assertEquals('Home Office', $todo->getLocation());
    }

    public function testSetAndGetUrl(): void
    {
        $todo = new VTodo();
        $todo->setUrl('https://example.com/task/12345');

        $this->assertEquals('https://example.com/task/12345', $todo->getUrl());
    }

    public function testSetAndGetStatus(): void
    {
        $todo = new VTodo();
        $todo->setStatus(VTodo::STATUS_IN_PROCESS);

        $this->assertEquals('IN-PROCESS', $todo->getStatus());
    }

    public function testSetInvalidStatus(): void
    {
        $todo = new VTodo();

        try {
            $todo->setStatus('INVALID_STATUS');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-VAL-002', $e->getErrorCode());
            $this->assertStringContainsString('Invalid VTODO status', $e->getMessage());
        }
    }

    public function testAllStatusValues(): void
    {
        $todo = new VTodo();

        $todo->setStatus(VTodo::STATUS_NEEDS_ACTION);
        $this->assertEquals('NEEDS-ACTION', $todo->getStatus());

        $todo->setStatus(VTodo::STATUS_COMPLETED);
        $this->assertEquals('COMPLETED', $todo->getStatus());

        $todo->setStatus(VTodo::STATUS_IN_PROCESS);
        $this->assertEquals('IN-PROCESS', $todo->getStatus());

        $todo->setStatus(VTodo::STATUS_CANCELLED);
        $this->assertEquals('CANCELLED', $todo->getStatus());
    }

    public function testSetAndGetCategories(): void
    {
        $todo = new VTodo();
        $todo->setCategories('work', 'urgent');

        $this->assertEquals(['work', 'urgent'], $todo->getCategories());
    }

    public function testAddAndGetAlarms(): void
    {
        $todo = new VTodo();
        $alarm1 = new VAlarm();
        $alarm1->setTrigger('-PT15M');

        $alarm2 = new VAlarm();
        $alarm2->setTrigger('-PT1H');

        $todo->addAlarm($alarm1);
        $todo->addAlarm($alarm2);

        $alarms = $todo->getAlarms();
        $this->assertCount(2, $alarms);
    }

    public function testGetAlarmReturnsEmptyArray(): void
    {
        $todo = new VTodo();

        $this->assertEmpty($todo->getAlarms());
    }

    public function testValidateMissingDtStamp(): void
    {
        $todo = new VTodo();
        $todo->setUid('todo-12345@example.com');

        try {
            $todo->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-001', $e->getErrorCode());
            $this->assertStringContainsString('DTSTAMP', $e->getMessage());
        }
    }

    public function testValidateMissingUid(): void
    {
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');

        try {
            $todo->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-002', $e->getErrorCode());
            $this->assertStringContainsString('UID', $e->getMessage());
        }
    }

    public function testValidateDueAndDurationMutuallyExclusive(): void
    {
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');
        $todo->setUid('todo-12345@example.com');
        $todo->setDue('20240220T170000Z');
        $todo->setDuration('PT2H');

        try {
            $todo->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VTODO-VAL-001', $e->getErrorCode());
            $this->assertStringContainsString('DUE', $e->getMessage());
            $this->assertStringContainsString('DURATION', $e->getMessage());
        }
    }

    public function testValidateSuccess(): void
    {
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');
        $todo->setUid('todo-12345@example.com');

        $this->assertNull($todo->validate());
    }

    public function testValidateSuccessWithDue(): void
    {
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');
        $todo->setUid('todo-12345@example.com');
        $todo->setDue('20240220T170000Z');

        $this->assertNull($todo->validate());
    }

    public function testValidateSuccessWithDuration(): void
    {
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');
        $todo->setUid('todo-12345@example.com');
        $todo->setDuration('PT2H');

        $this->assertNull($todo->validate());
    }

    public function testFluentInterface(): void
    {
        $todo = new VTodo();

        $result = $todo->setDtStamp('20240215T120000Z')
            ->setUid('todo-12345@example.com')
            ->setSummary('Complete report')
            ->setStatus(VTodo::STATUS_IN_PROCESS)
            ->setPercentComplete(25)
            ->setPriority(1);

        $this->assertSame($todo, $result);
        $this->assertEquals('20240215T120000Z', $todo->getDtStamp());
        $this->assertEquals('todo-12345@example.com', $todo->getUid());
        $this->assertEquals('Complete report', $todo->getSummary());
        $this->assertEquals('IN-PROCESS', $todo->getStatus());
        $this->assertEquals(25, $todo->getPercentComplete());
        $this->assertEquals(1, $todo->getPriority());
    }

    public function testGetDtStampWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getDtStamp());
    }

    public function testGetUidWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getUid());
    }

    public function testGetDtStartWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getDtStart());
    }

    public function testGetDueWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getDue());
    }

    public function testGetCompletedWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getCompleted());
    }

    public function testGetDurationWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getDuration());
    }

    public function testGetPercentCompleteWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getPercentComplete());
    }

    public function testGetPriorityWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getPriority());
    }

    public function testGetSummaryWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getSummary());
    }

    public function testGetDescriptionWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getDescription());
    }

    public function testGetLocationWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getLocation());
    }

    public function testGetUrlWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getUrl());
    }

    public function testGetStatusWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertNull($todo->getStatus());
    }

    public function testGetCategoriesWhenNotSet(): void
    {
        $todo = new VTodo();

        $this->assertEmpty($todo->getCategories());
    }

    public function testCategoriesSingleValue(): void
    {
        $todo = new VTodo();
        $todo->setCategories('important');

        $this->assertEquals(['important'], $todo->getCategories());
    }

    public function testCategoriesEmpty(): void
    {
        $todo = new VTodo();
        $todo->setCategories('');

        $this->assertEquals([], $todo->getCategories());
    }

    public function testOverwriteDtStamp(): void
    {
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');
        $todo->setDtStamp('20240216T120000Z');

        $this->assertEquals('20240216T120000Z', $todo->getDtStamp());
        $this->assertCount(1, $todo->getProperties());
    }

    public function testOverwriteUid(): void
    {
        $todo = new VTodo();
        $todo->setUid('todo-1@example.com');
        $todo->setUid('todo-2@example.com');

        $this->assertEquals('todo-2@example.com', $todo->getUid());
        $this->assertCount(1, $todo->getProperties());
    }

    public function testAddAlarmWithFluentInterface(): void
    {
        $todo = new VTodo();
        $alarm = new VAlarm();

        $result = $todo->addAlarm($alarm);

        $this->assertSame($todo, $result);
        $this->assertCount(1, $todo->getAlarms());
    }

    public function testRemoveAlarm(): void
    {
        $todo = new VTodo();
        $alarm1 = new VAlarm();
        $alarm1->setTrigger('-PT15M');

        $alarm2 = new VAlarm();
        $alarm2->setTrigger('-PT1H');

        $todo->addAlarm($alarm1);
        $todo->addAlarm($alarm2);
        $this->assertCount(2, $todo->getAlarms());

        $todo->removeComponent($alarm1);
        $this->assertCount(1, $todo->getAlarms());
    }

    public function testParentIsSetWhenAddedToCalendar(): void
    {
        $calendar = new \Icalendar\Component\VCalendar();
        $todo = new VTodo();
        $todo->setDtStamp('20240215T120000Z');
        $todo->setUid('todo-12345@example.com');

        $calendar->addComponent($todo);

        $this->assertNull($calendar->getParent());
        $this->assertSame($calendar, $todo->getParent());
    }
}
