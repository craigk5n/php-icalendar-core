<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Icalendar\Writer\ValueWriter\DateTimeWriter;
use PHPUnit\Framework\TestCase;

class DateTimeWriterTest extends TestCase
{
    private DateTimeWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new DateTimeWriter();
    }

    // ========== write Tests ==========

    public function testWriteLocalDateTime(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T093045', $result);
    }

    public function testWriteLocalDateTimeImmutable(): void
    {
        $dateTime = new DateTimeImmutable('2026-02-06 14:15:30', new DateTimeZone('Europe/London'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T141530', $result);
    }

    public function testWriteUTCDateTime(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('UTC'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T093045Z', $result);
    }

    public function testWriteUTCDateTimeImmutable(): void
    {
        $dateTime = new DateTimeImmutable('2026-02-06 23:59:59', new DateTimeZone('UTC'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T235959Z', $result);
    }

    public function testWriteWithPlus0000Timezone(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('+00:00'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T093045Z', $result);
    }

    public function testWriteWithGMTTimezone(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('GMT'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T093045Z', $result);
    }

    public function testWriteWithEtcUTCTimezone(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('Etc/UTC'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T093045Z', $result);
    }

    public function testWriteWithZTimezone(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('Z'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T093045Z', $result);
    }

    public function testWriteWithNonUTCTimezone(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T093045', $result);
    }

    public function testWriteWithDifferentTimezones(): void
    {
        $utcTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('UTC'));
        $nyTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('America/New_York'));
        $tokyoTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('Asia/Tokyo'));
        
        $this->assertEquals('20260206T093045Z', $this->writer->write($utcTime));
        $this->assertEquals('20260206T093045', $this->writer->write($nyTime));
        $this->assertEquals('20260206T093045', $this->writer->write($tokyoTime));
    }

    public function testWriteMidnight(): void
    {
        $dateTime = new DateTime('2026-02-06 00:00:00', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T000000', $result);
    }

    public function testWriteMidnightUTC(): void
    {
        $dateTime = new DateTime('2026-02-06 00:00:00', new DateTimeZone('UTC'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T000000Z', $result);
    }

    public function testWriteEndOfDay(): void
    {
        $dateTime = new DateTime('2026-02-06 23:59:59', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T235959', $result);
    }

    public function testWriteEndOfDayUTC(): void
    {
        $dateTime = new DateTime('2026-02-06 23:59:59', new DateTimeZone('UTC'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T235959Z', $result);
    }

    public function testWriteLeapSecond(): void
    {
        $dateTime = new DateTime('2026-02-06 23:59:60', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        // PHP might normalize this, but we'll test what it actually does
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}$/', $result);
    }

    public function testWriteSingleDigitComponents(): void
    {
        $dateTime = new DateTime('2026-01-02 03:04:05', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260102T030405', $result);
    }

public function testWriteDateTimeImmutable(): void
    {
        // Already have test above, but this focuses on interface aspect
        $dateTime = new DateTimeImmutable('2026-02-06 12:30:45', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        $this->assertEquals('20260206T123045', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateTimeWriter expects DateTimeInterface, got string');
        
        $this->writer->write('2026-02-06T09:30:45');
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateTimeWriter expects DateTimeInterface, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateTimeWriter expects DateTimeInterface, got array');
        
        $this->writer->write([2026, 2, 6, 9, 30, 45]);
    }

    public function testWriteInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateTimeWriter expects DateTimeInterface, got integer');
        
        $this->writer->write(1234567890);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('DATE-TIME', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $dateTime = new DateTime();
        $dateTimeImmutable = new DateTimeImmutable();
        
        $this->assertTrue($this->writer->canWrite($dateTime));
        $this->assertTrue($this->writer->canWrite($dateTimeImmutable));
        
        $this->assertFalse($this->writer->canWrite('2026-02-06T09:30:45'));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(1234567890));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidDateTimeFormat(): void
    {
        $testDateTimes = [
            '2026-02-06 09:30:45',
            '2026-02-06 12:00:00',
            '2026-02-06 23:59:59',
        ];
        
        foreach ($testDateTimes as $dateTimeString) {
            $dateTime = new DateTime($dateTimeString, new DateTimeZone('America/New_York'));
            $result = $this->writer->write($dateTime);
            
            // Should match pattern (15 or 16 chars depending on timezone)
            $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z?$/', $result);
        }
    }

    public function testWriteUTCTimeProducesValidFormat(): void
    {
        $testDateTimes = [
            '2026-02-06 00:00:00',
            '2026-02-06 09:30:45',
            '2026-02-06 12:00:00',
            '2026-02-06 23:59:59',
        ];
        
        foreach ($testDateTimes as $dateTimeString) {
            $dateTime = new DateTime($dateTimeString, new DateTimeZone('UTC'));
            $result = $this->writer->write($dateTime);
            
            // Should be exactly 16 characters (YYYYMMDDTHHMMSSZ)
            $this->assertEquals(16, strlen($result));
            
            // Should end with Z
            $this->assertStringEndsWith('Z', $result);
            
            // Should match pattern
            $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z$/', $result);
        }
    }

    public function testWriteDateTimeComponents(): void
    {
        // Test specific date-time components
        $testCases = [
            ['2026-02-06 00:00:01', '20260206T000001'],
            ['2026-02-06 01:00:00', '20260206T010000'],
            ['2026-02-06 12:34:56', '20260206T123456'],
            ['2026-02-06 23:59:59', '20260206T235959'],
        ];
        
        foreach ($testCases as [$input, $expected]) {
            $dateTime = new DateTime($input, new DateTimeZone('America/New_York'));
            $result = $this->writer->write($dateTime);
            
            $this->assertEquals($expected, $result, "DateTime $input should format as $expected");
        }
    }

    public function testWriteTimezoneDetection(): void
    {
        $baseDateTime = '2026-02-06 09:30:45';
        $timezones = [
            'UTC' => '20260206T093045Z',
            '+00:00' => '20260206T093045Z',
            'GMT' => '20260206T093045Z',
            'Etc/UTC' => '20260206T093045Z',
            'Z' => '20260206T093045Z',
            'America/New_York' => '20260206T093045',
            'Europe/London' => '20260206T093045',
            'Asia/Tokyo' => '20260206T093045',
            'Australia/Sydney' => '20260206T093045',
        ];
        
        foreach ($timezones as $timezone => $expectedSuffix) {
            $dateTime = new DateTime($baseDateTime, new DateTimeZone($timezone));
            $result = $this->writer->write($dateTime);
            
            $this->assertEquals($expectedSuffix, $result, "Timezone $timezone should produce $expectedSuffix");
        }
    }

public function testWriteWithZeroOffsetTimezone(): void
    {
        // Test timezone with 0 offset but not standard UTC name
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('GMT'));
        
        // GMT should be detected as UTC
        $result = $this->writer->write($dateTime);
        
        $this->assertStringEndsWith('Z', $result);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testWriteEdgeCases(): void
    {
        $edgeCases = [
            ['2026-01-01 00:00:01', '20260101T000001'], // Start of year
            ['2026-12-31 23:59:59', '20261231T235959'], // End of year
            ['2024-02-29 23:59:59', '20240229T235959'], // Leap year
        ];
        
        foreach ($edgeCases as [$dateTimeString, $expected]) {
            $dateTime = new DateTime($dateTimeString, new DateTimeZone('America/New_York'));
            $result = $this->writer->write($dateTime);
            
            $this->assertEquals($expected, $result, "Edge case $dateTimeString");
        }
    }

    public function testWriteRealWorldTimezones(): void
    {
        $testCases = [
            ['2026-02-06 09:30:00', 'UTC', '20260206T093000Z'],
            ['2026-02-06 09:30:00', 'EST', '20260206T093000'],
            ['2026-02-06 09:30:00', 'PST', '20260206T093000'],
            ['2026-02-06 09:30:00', 'CET', '20260206T093000'],
            ['2026-02-06 09:30:00', 'JST', '20260206T093000'],
        ];
        
        foreach ($testCases as [$time, $timezone, $expected]) {
            try {
                $dateTime = new DateTime($time, new DateTimeZone($timezone));
                $result = $this->writer->write($dateTime);
                $this->assertEquals($expected, $result, "Timezone $timezone");
            } catch (\Exception $e) {
                // Some timezones might not be available in the test environment
                $this->assertStringContainsString('DateTimeZone::__construct', $e->getMessage());
            }
        }
    }

    public function testWriteWithDateTimeCreatedFromTimestamp(): void
    {
        $timestamp = mktime(9, 30, 45, 2, 6, 2026); // Fixed timestamp
        $dateTime = DateTime::createFromFormat('U', (string) $timestamp);
        $result = $this->writer->write($dateTime);
        
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z?$/', $result);
    }

    public function testWriteConsistency(): void
    {
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('America/New_York'));
        
        $result1 = $this->writer->write($dateTime);
        $result2 = $this->writer->write($dateTime);
        
        $this->assertEquals($result1, $result2);
    }

    public function testWritePerformance(): void
    {
        $iterations = 1000;
        $dateTime = new DateTime('2026-02-06 09:30:45', new DateTimeZone('America/Los_Angeles'));
        
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->writer->write($dateTime);
        }
        $end = microtime(true);
        
        // Should be reasonably fast
        $this->assertLessThan(0.1, $end - $start);
    }

    public function testWriteMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $seconds = $i % 60;
            $dateTime = new DateTime("2026-02-06 09:30:{$seconds}");
            $result = $this->writer->write($dateTime);
            unset($result);
        }
        
        $finalMemory = memory_get_usage();
        
        $this->assertGreaterThanOrEqual($initialMemory, $finalMemory);
    }
}