<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Icalendar\Writer\ValueWriter\TimeWriter;
use PHPUnit\Framework\TestCase;

class TimeWriterTest extends TestCase
{
    private TimeWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new TimeWriter();
    }

    // ========== write Tests ==========

    public function testWriteLocalTime(): void
    {
        $time = new DateTime('09:30:45', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('093045', $result);
    }

    public function testWriteLocalTimeImmutable(): void
    {
        $time = new DateTimeImmutable('14:15:30', new DateTimeZone('America/Los_Angeles'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('141530', $result);
    }

    public function testWriteUTCTime(): void
    {
        $time = new DateTime('09:30:45', new DateTimeZone('UTC'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('093045Z', $result);
    }

    public function testWriteUTCTimeImmutable(): void
    {
        $time = new DateTimeImmutable('23:59:59', new DateTimeZone('UTC'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('235959Z', $result);
    }

    public function testWriteWithPlus0000Timezone(): void
    {
        $time = new DateTime('09:30:45', new DateTimeZone('+00:00'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('093045Z', $result);
    }

    public function testWriteWithNonUTCTimezone(): void
    {
        $time = new DateTime('09:30:45', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('093045', $result);
    }

    public function testWriteWithDifferentTimezones(): void
    {
        $utcTime = new DateTime('09:30:45', new DateTimeZone('UTC'));
        $nyTime = new DateTime('09:30:45', new DateTimeZone('America/New_York'));
        $tokyoTime = new DateTime('09:30:45', new DateTimeZone('Asia/Tokyo'));
        
        $this->assertEquals('093045Z', $this->writer->write($utcTime));
        $this->assertEquals('093045', $this->writer->write($nyTime));
        $this->assertEquals('093045', $this->writer->write($tokyoTime));
    }

    public function testWriteMidnight(): void
    {
        $time = new DateTime('00:00:00', new DateTimeZone('America/New_York'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('000000', $result);
    }

    public function testWriteMidnightUTC(): void
    {
        $time = new DateTime('00:00:00', new DateTimeZone('UTC'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('000000Z', $result);
    }

    public function testWriteEndOfDay(): void
    {
        $time = new DateTime('23:59:59', new DateTimeZone('America/Chicago'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('235959', $result);
    }

    public function testWriteEndOfDayUTC(): void
    {
        $time = new DateTime('23:59:59', new DateTimeZone('UTC'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('235959Z', $result);
    }

    public function testWriteLeapSecond(): void
    {
        // PHP DateTime doesn't support leap seconds (60), it normalizes to next day
        $time = new DateTime('23:59:60', new DateTimeZone('Europe/London'));
        $result = $this->writer->write($time);
        
        if ($result === '000000') {
            $this->markTestSkipped('PHP normalizes leap second 60 to 00:00:00');
        }
        
        $this->assertEquals('235960', $result);
    }

    public function testWriteSingleDigitHour(): void
    {
        $time = new DateTime('01:02:03', new DateTimeZone('Asia/Tokyo'));
        $result = $this->writer->write($time);
        
        $this->assertEquals('010203', $result);
    }

    public function testWriteDateTimeInterface(): void
    {
        // Use real DateTimeImmutable instead of mock (PHP 8.2+ disallows mocking DateTimeInterface)
        $dateTime = new DateTimeImmutable('12:30:45', new DateTimeZone('UTC'));

        $result = $this->writer->write($dateTime);

        $this->assertEquals('123045Z', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TimeWriter expects DateTimeInterface or string, got array');
        
        $this->writer->write(['093045']);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TimeWriter expects DateTimeInterface or string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TimeWriter expects DateTimeInterface or string, got array');
        
        $this->writer->write([9, 30, 45]);
    }

    public function testWriteInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TimeWriter expects DateTimeInterface or string, got integer');
        
        $this->writer->write(123045);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('TIME', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $dateTime = new DateTime();
        $dateTimeImmutable = new DateTimeImmutable();
        $this->assertTrue($this->writer->canWrite($dateTime));
        $this->assertTrue($this->writer->canWrite($dateTimeImmutable));
        $this->assertTrue($this->writer->canWrite('093045Z'));
        
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(123045));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidTimeFormat(): void
    {
        $testTimes = [
            '00:00:00',
            '01:02:03',
            '09:30:45',
            '12:00:00',
            '15:30:00',
            '23:59:59',
        ];
        
        $tz = new DateTimeZone('America/New_York');
        foreach ($testTimes as $timeString) {
            $time = new DateTime($timeString, $tz);
            $result = $this->writer->write($time);
            
            // Should be exactly 6 digits
            $this->assertEquals(6, strlen($result), "Format failed for $timeString");
            
            // Should be numeric
            $this->assertMatchesRegularExpression('/^\d{6}$/', $result);
        }
    }

    public function testWriteUTCTimeProducesValidFormat(): void
    {
        $testTimes = [
            '00:00:00',
            '09:30:45',
            '12:00:00',
            '23:59:59',
        ];
        
        foreach ($testTimes as $timeString) {
            $time = new DateTime($timeString, new DateTimeZone('UTC'));
            $result = $this->writer->write($time);
            
            // Should be 6 digits + Z
            $this->assertEquals(7, strlen($result));
            
            // Should end with Z
            $this->assertStringEndsWith('Z', $result);
            
            // Should be numeric before Z
            $numericPart = substr($result, 0, 6);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $numericPart);
        }
    }

    public function testWriteTimeComponents(): void
    {
        // Test specific time components
        $testCases = [
            ['00:00:00', '000000'],
            ['00:00:01', '000001'],
            ['01:00:00', '010000'],
            ['00:01:00', '000100'],
            ['00:00:30', '000030'],
            ['12:34:56', '123456'],
            ['23:59:59', '235959'],
        ];
        
        $tz = new DateTimeZone('America/New_York');
        foreach ($testCases as [$input, $expected]) {
            $time = new DateTime($input, $tz);
            $result = $this->writer->write($time);
            
            $this->assertEquals($expected, $result, "Time $input should format as $expected");
        }
    }

    public function testWriteTimezoneDetection(): void
    {
        $baseTime = '09:30:45';
        $timezones = [
            'UTC' => '093045Z',
            '+00:00' => '093045Z',
            'America/New_York' => '093045',
            'Europe/London' => '093045',
            'Asia/Tokyo' => '093045',
            'Australia/Sydney' => '093045',
        ];
        
        foreach ($timezones as $timezone => $expectedSuffix) {
            $time = new DateTime($baseTime, new DateTimeZone($timezone));
            $result = $this->writer->write($time);
            
            $this->assertEquals($expectedSuffix, $result, "Timezone $timezone should produce $expectedSuffix");
        }
    }

    public function testWriteWithDateComponentsIgnored(): void
    {
        // Test that date components are ignored
        $dateTimes = [
            '2026-02-06 09:30:45',
            '2023-12-31 23:59:59',
            '2000-01-01 00:00:00',
        ];
        
        $tz = new DateTimeZone('America/New_York');
        foreach ($dateTimes as $dateTimeString) {
            $dateTime = new DateTime($dateTimeString, $tz);
            $result = $this->writer->write($dateTime);
            
            // Should only contain time components
            $this->assertMatchesRegularExpression('/^\d{6}$/', $result);
            
            // Extract time part from original and compare
            $timePart = substr($dateTimeString, -8); // HH:MM:SS
            $expected = str_replace(':', '', $timePart);
            $this->assertEquals($expected, $result);
        }
    }

    public function testWriteRealWorldTimezones(): void
    {
        $testCases = [
            ['09:30:00', 'UTC', '093000Z'],
            ['09:30:00', 'EST', '093000'],
            ['09:30:00', 'PST', '093000'],
            ['09:30:00', 'CET', '093000'],
            ['09:30:00', 'JST', '093000'],
        ];
        
        foreach ($testCases as [$time, $timezone, $expected]) {
            try {
                $dateTime = new DateTime($time, new DateTimeZone($timezone));
                $result = $this->writer->write($dateTime);
                $this->assertEquals($expected, $result, "Timezone $timezone");
            } catch (\Exception $e) {
                // Some timezones might not be available in the test environment
                if (str_contains($e->getMessage(), 'DateTimeZone::__construct')) {
                    continue;
                }
                throw $e;
            }
        }
    }

    public function testWriteWithDateTimeCreatedFromTimestamp(): void
    {
        $timestamp = time();
        $dateTime = DateTime::createFromFormat('U', (string) $timestamp);
        // Force a non-UTC timezone to avoid 'Z'
        $dateTime->setTimezone(new DateTimeZone('America/New_York'));
        $result = $this->writer->write($dateTime);
        
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result);
    }
}
