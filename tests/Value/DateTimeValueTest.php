<?php

declare(strict_types=1);

namespace Icalendar\Tests\Value;

use Icalendar\Value\DateTimeValue;
use PHPUnit\Framework\TestCase;

class DateTimeValueTest extends TestCase
{
    public function testConstructorWithDateTime(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T103000Z', $value->getRawValue());
        $this->assertSame($dateTime, $value->getValue());
    }

    public function testConstructorWithDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable('2026-02-06 10:30:00', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T103000Z', $value->getRawValue());
        $this->assertSame($dateTime, $value->getValue());
    }

    public function testGetTypeReturnsDateTime(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00');
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('DATE-TIME', $value->getType());
    }

    public function testGetTypeReturnsDate(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00');
        $value = new DateTimeValue($dateTime, 'DATE');
        
        $this->assertEquals('DATE', $value->getType());
    }

    public function testGetRawValue(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T103000Z', $value->getRawValue());
    }

    public function testGetValue(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00');
        $value = new DateTimeValue($dateTime);
        
        $this->assertSame($dateTime, $value->getValue());
    }

    public function testSerialize(): void
    {
        // Default DateTime has local timezone unless specified.
        // Assuming it's UTC or local for this test. 
        // Let's use a fixed timezone to be sure.
        $dateTime = new \DateTime('2026-02-06 10:30:00', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T103000Z', $value->serialize());
    }

    public function testSerializeWithMidnight(): void
    {
        $dateTime = new \DateTime('2026-02-06 00:00:00', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T000000Z', $value->serialize());
    }

    public function testSerializeWithEndOfDay(): void
    {
        $dateTime = new \DateTime('2026-02-06 23:59:59', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T235959Z', $value->serialize());
    }

    public function testIsDefaultReturnsTrueForDateTime(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00');
        $value = new DateTimeValue($dateTime, 'DATE-TIME');
        
        $this->assertTrue($value->isDefault());
    }

    public function testIsDefaultReturnsFalseForDate(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00');
        $value = new DateTimeValue($dateTime, 'DATE');
        
        $this->assertFalse($value->isDefault());
    }

    public function testWithDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable('2026-02-06 15:45:30', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T154530Z', $value->serialize());
        $this->assertSame($dateTime, $value->getValue());
    }

    public function testWithTimezone(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00', new \DateTimeZone('America/New_York'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T103000', $value->serialize());
    }

    public function testWithUTC(): void
    {
        $dateTime = new \DateTime('2026-02-06 10:30:00', new \DateTimeZone('UTC'));
        $value = new DateTimeValue($dateTime);
        
        $this->assertEquals('20260206T103000Z', $value->serialize());
    }
}
