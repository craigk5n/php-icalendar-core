<?php

declare(strict_types=1);

namespace Icalendar\Tests\Component;

use Icalendar\Component\VJournal;
use Icalendar\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VJournal component
 */
class VJournalTest extends TestCase
{
    public function testCreateVJournal(): void
    {
        $journal = new VJournal();

        $this->assertEquals('VJOURNAL', $journal->getName());
    }

    public function testSetAndGetDtStamp(): void
    {
        $journal = new VJournal();
        $journal->setDtStamp('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $journal->getDtStamp());
    }

    public function testSetAndGetUid(): void
    {
        $journal = new VJournal();
        $journal->setUid('journal-12345@example.com');

        $this->assertEquals('journal-12345@example.com', $journal->getUid());
    }

    public function testSetAndGetDtStart(): void
    {
        $journal = new VJournal();
        $journal->setDtStart('20240215T120000Z');

        $this->assertEquals('20240215T120000Z', $journal->getDtStart());
    }

    public function testSetAndGetDtStartDateOnly(): void
    {
        $journal = new VJournal();
        $journal->setDtStart('20240215');

        $this->assertEquals('20240215', $journal->getDtStart());
    }

    public function testSetAndGetSummary(): void
    {
        $journal = new VJournal();
        $journal->setSummary('Daily Reflection');

        $this->assertEquals('Daily Reflection', $journal->getSummary());
    }

    public function testSetAndGetDescription(): void
    {
        $journal = new VJournal();
        $journal->setDescription('Today was a productive day.');

        $this->assertEquals('Today was a productive day.', $journal->getDescription());
    }

    public function testAddMultipleDescriptions(): void
    {
        $journal = new VJournal();
        $journal->addDescription('First paragraph of the journal entry.');
        $journal->addDescription('Second paragraph with more details.');
        $journal->addDescription('Third paragraph concluding thoughts.');

        $descriptions = $journal->getDescriptions();
        $this->assertCount(3, $descriptions);
        $this->assertEquals('First paragraph of the journal entry.', $descriptions[0]);
        $this->assertEquals('Second paragraph with more details.', $descriptions[1]);
        $this->assertEquals('Third paragraph concluding thoughts.', $descriptions[2]);
    }

    public function testSetDescriptionReplacesExisting(): void
    {
        $journal = new VJournal();
        $journal->addDescription('First entry');
        $journal->addDescription('Second entry');
        $journal->setDescription('Replaced entry');

        $descriptions = $journal->getDescriptions();
        $this->assertCount(1, $descriptions);
        $this->assertEquals('Replaced entry', $descriptions[0]);
    }

    public function testGetDescriptionReturnsFirst(): void
    {
        $journal = new VJournal();
        $journal->addDescription('First entry');
        $journal->addDescription('Second entry');

        $this->assertEquals('First entry', $journal->getDescription());
    }

    public function testGetDescriptionsWhenEmpty(): void
    {
        $journal = new VJournal();

        $this->assertEmpty($journal->getDescriptions());
    }

    public function testSetAndGetCategories(): void
    {
        $journal = new VJournal();
        $journal->setCategories('personal', 'reflection');

        $this->assertEquals(['personal', 'reflection'], $journal->getCategories());
    }

    public function testSetAndGetClass(): void
    {
        $journal = new VJournal();
        $journal->setClass(VJournal::CLASS_PRIVATE);

        $this->assertEquals('PRIVATE', $journal->getClass());
    }

    public function testAllClassValues(): void
    {
        $journal = new VJournal();

        $journal->setClass(VJournal::CLASS_PUBLIC);
        $this->assertEquals('PUBLIC', $journal->getClass());

        $journal->setClass(VJournal::CLASS_PRIVATE);
        $this->assertEquals('PRIVATE', $journal->getClass());

        $journal->setClass(VJournal::CLASS_CONFIDENTIAL);
        $this->assertEquals('CONFIDENTIAL', $journal->getClass());
    }

    public function testSetInvalidClass(): void
    {
        $journal = new VJournal();

        try {
            $journal->setClass('INVALID_CLASS');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VJOURNAL-VAL-002', $e->getErrorCode());
            $this->assertStringContainsString('CLASS', $e->getMessage());
        }
    }

    public function testSetAndGetStatus(): void
    {
        $journal = new VJournal();
        $journal->setStatus(VJournal::STATUS_DRAFT);

        $this->assertEquals('DRAFT', $journal->getStatus());
    }

    public function testAllStatusValues(): void
    {
        $journal = new VJournal();

        $journal->setStatus(VJournal::STATUS_DRAFT);
        $this->assertEquals('DRAFT', $journal->getStatus());

        $journal->setStatus(VJournal::STATUS_FINAL);
        $this->assertEquals('FINAL', $journal->getStatus());

        $journal->setStatus(VJournal::STATUS_CANCELLED);
        $this->assertEquals('CANCELLED', $journal->getStatus());
    }

    public function testSetInvalidStatus(): void
    {
        $journal = new VJournal();

        try {
            $journal->setStatus('INVALID_STATUS');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VJOURNAL-VAL-001', $e->getErrorCode());
            $this->assertStringContainsString('Invalid VJOURNAL status', $e->getMessage());
        }
    }

    public function testSetAndGetRrule(): void
    {
        $journal = new VJournal();
        $journal->setRrule('FREQ=DAILY;COUNT=5');

        $this->assertEquals('FREQ=DAILY;COUNT=5', $journal->getRrule());
    }

    public function testValidateMissingDtStamp(): void
    {
        $journal = new VJournal();
        $journal->setUid('journal-12345@example.com');

        try {
            $journal->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VJOURNAL-001', $e->getErrorCode());
            $this->assertStringContainsString('DTSTAMP', $e->getMessage());
        }
    }

    public function testValidateMissingUid(): void
    {
        $journal = new VJournal();
        $journal->setDtStamp('20240215T120000Z');

        try {
            $journal->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertEquals('ICAL-VJOURNAL-002', $e->getErrorCode());
            $this->assertStringContainsString('UID', $e->getMessage());
        }
    }

    public function testValidateSuccess(): void
    {
        $journal = new VJournal();
        $journal->setDtStamp('20240215T120000Z');
        $journal->setUid('journal-12345@example.com');

        $this->assertNull($journal->validate());
    }

    public function testFluentInterface(): void
    {
        $journal = new VJournal();

        $result = $journal->setDtStamp('20240215T120000Z')
            ->setUid('journal-12345@example.com')
            ->setSummary('Daily Reflection')
            ->setStatus(VJournal::STATUS_DRAFT)
            ->setClass(VJournal::CLASS_PRIVATE);

        $this->assertSame($journal, $result);
        $this->assertEquals('20240215T120000Z', $journal->getDtStamp());
        $this->assertEquals('journal-12345@example.com', $journal->getUid());
        $this->assertEquals('Daily Reflection', $journal->getSummary());
        $this->assertEquals('DRAFT', $journal->getStatus());
        $this->assertEquals('PRIVATE', $journal->getClass());
    }

    public function testAddDescriptionFluentInterface(): void
    {
        $journal = new VJournal();

        $result = $journal->addDescription('First entry');

        $this->assertSame($journal, $result);
    }

    public function testGetDtStampWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getDtStamp());
    }

    public function testGetUidWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getUid());
    }

    public function testGetDtStartWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getDtStart());
    }

    public function testGetSummaryWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getSummary());
    }

    public function testGetDescriptionWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getDescription());
    }

    public function testGetClassWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getClass());
    }

    public function testGetStatusWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getStatus());
    }

    public function testGetRruleWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertNull($journal->getRrule());
    }

    public function testGetCategoriesWhenNotSet(): void
    {
        $journal = new VJournal();

        $this->assertEmpty($journal->getCategories());
    }

    public function testCategoriesSingleValue(): void
    {
        $journal = new VJournal();
        $journal->setCategories('important');

        $this->assertEquals(['important'], $journal->getCategories());
    }

    public function testCategoriesEmpty(): void
    {
        $journal = new VJournal();
        $journal->setCategories('');

        $this->assertEquals([], $journal->getCategories());
    }

    public function testOverwriteDtStamp(): void
    {
        $journal = new VJournal();
        $journal->setDtStamp('20240215T120000Z');
        $journal->setDtStamp('20240216T120000Z');

        $this->assertEquals('20240216T120000Z', $journal->getDtStamp());
    }

    public function testOverwriteUid(): void
    {
        $journal = new VJournal();
        $journal->setUid('journal-1@example.com');
        $journal->setUid('journal-2@example.com');

        $this->assertEquals('journal-2@example.com', $journal->getUid());
    }

    public function testParentIsSetWhenAddedToCalendar(): void
    {
        $calendar = new \Icalendar\Component\VCalendar();
        $journal = new VJournal();
        $journal->setDtStamp('20240215T120000Z');
        $journal->setUid('journal-12345@example.com');

        $calendar->addComponent($journal);

        $this->assertNull($calendar->getParent());
        $this->assertSame($calendar, $journal->getParent());
    }

    public function testRecurringJournalEntry(): void
    {
        $journal = new VJournal();
        $journal->setDtStamp('20240215T120000Z');
        $journal->setUid('journal-12345@example.com');
        $journal->setDtStart('20240101');
        $journal->setRrule('FREQ=WEEKLY;BYDAY=SU;COUNT=52');
        $journal->setSummary('Weekly Reflection');

        $this->assertEquals('FREQ=WEEKLY;BYDAY=SU;COUNT=52', $journal->getRrule());
        $this->assertNull($journal->validate());
    }
}
