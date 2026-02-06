<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Icalendar\Writer\ValueWriter\DateWriter;
use PHPUnit\Framework\TestCase;

class DateWriterTest extends TestCase
{
    private DateWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new DateWriter();
    }

    // ========== write Tests ==========

    public function testWriteDateTime(): void
    {
        $date = new DateTime('2026-02-06 15:30:45');
        $result = $this->writer->write($date);
        
        $this->assertEquals('20260206', $result);
    }

    public function testWriteDateTimeImmutable(): void
    {
        $date = new DateTimeImmutable('2026-02-06 15:30:45');
        $result = $this->writer->write($date);
        
        $this->assertEquals('20260206', $result);
    }

    public function testWriteDateOnly(): void
    {
        $date = new DateTime('2026-02-06');
        $result = $this->writer->write($date);
        
        $this->assertEquals('20260206', $result);
    }

    public function testWriteWithDifferentTimezones(): void
    {
        $utc = new DateTime('2026-02-06 15:30:45', new \DateTimeZone('UTC'));
        $ny = new DateTime('2026-02-06 10:30:45', new \DateTimeZone('America/New_York'));
        $tokyo = new DateTime('2026-02-07 00:30:45', new \DateTimeZone('Asia/Tokyo'));
        
        // All should produce the same local date, regardless of timezone
        $this->assertEquals('20260206', $this->writer->write($utc));
        $this->assertEquals('20260206', $this->writer->write($ny));
        $this->assertEquals('20260207', $this->writer->write($tokyo));
    }

    public function testWriteEdgeCases(): void
    {
        // First day of year
        $newYear = new DateTime('2026-01-01 23:59:59');
        $this->assertEquals('20260101', $this->writer->write($newYear));
        
        // Last day of year
        $newYearsEve = new DateTime('2026-12-31 00:00:01');
        $this->assertEquals('20261231', $this->writer->write($newYearsEve));
        
        // Leap year February 29
        $leapDay = new DateTime('2024-02-29 12:00:00');
        $this->assertEquals('20240229', $this->writer->write($leapDay));
        
        // Non-leap year February 28
        $feb28 = new DateTime('2023-02-28 12:00:00');
        $this->assertEquals('20230228', $this->writer->write($feb28));
    }

    public function testWriteWithMidnightTime(): void
    {
        $midnight = new DateTime('2026-02-06 00:00:00');
        $result = $this->writer->write($midnight);
        
        $this->assertEquals('20260206', $result);
    }

    public function testWriteWithEndOfDay(): void
    {
        $endOfDay = new DateTime('2026-02-06 23:59:59');
        $result = $this->writer->write($endOfDay);
        
        $this->assertEquals('20260206', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateWriter expects DateTimeInterface, got string');
        
        $this->writer->write('20260206');
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateWriter expects DateTimeInterface, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateWriter expects DateTimeInterface, got array');
        
        $this->writer->write(['2026', '02', '06']);
    }

    public function testWriteInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateWriter expects DateTimeInterface, got integer');
        
        $this->writer->write(20260206);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('DATE', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $dateTime = new DateTime();
        $dateTimeImmutable = new DateTimeImmutable();
        $this->assertTrue($this->writer->canWrite($dateTime));
        $this->assertTrue($this->writer->canWrite($dateTimeImmutable));
        
        $this->assertFalse($this->writer->canWrite('20260206'));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(20260206));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidDateFormat(): void
    {
        $dates = [
            '2026-01-01',
            '2026-12-31',
            '2024-02-29', // Leap year
            '2023-02-28', // Non-leap year
            '2000-01-01', // Y2K
            '1999-12-31', // Pre-Y2K
        ];
        
        foreach ($dates as $dateString) {
            $date = new DateTime($dateString);
            $result = $this->writer->write($date);
            
            // Verify format: exactly 8 digits
            $this->assertMatchesRegularExpression('/^\d{8}$/', $result);
            
            // Verify it represents the same date
            $parsed = DateTime::createFromFormat('Ymd', $result);
            $this->assertEquals($date->format('Y-m-d'), $parsed->format('Y-m-d'));
        }
    }

    public function testWriteIgnoresTime(): void
    {
        $baseDate = '2026-02-06';
        $times = [
            '00:00:00',
            '01:23:45',
            '12:00:00',
            '23:59:59',
        ];
        
        foreach ($times as $time) {
            $dateTime = new DateTime("$baseDate $time");
            $result = $this->writer->write($dateTime);
            
            $this->assertEquals('20260206', $result, "Time should be ignored for date: $time");
        }
    }

    public function testWriteWithDifferentDateTimeFormats(): void
    {
        $baseDate = '2026-02-06';
        $formats = [
            'Y-m-d',
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y/m/d',
            'd.m.Y',
        ];
        
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, '2026-02-06');
            if ($dateTime !== false) {
                $result = $this->writer->write($dateTime);
                $this->assertEquals('20260206', $result, "Format: $format");
            }
        }
    }
}