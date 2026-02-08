<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use DateInterval;
use Icalendar\Writer\ValueWriter\DurationWriter;
use PHPUnit\Framework\TestCase;

class DurationWriterTest extends TestCase
{
    private DurationWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new DurationWriter();
    }

    // ========== write Tests ==========

    public function testWriteSimpleDuration(): void
    {
        $interval = new DateInterval('PT1H');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('PT1H', $result);
    }

    public function testWriteMinutesDuration(): void
    {
        $interval = new DateInterval('PT30M');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('PT30M', $result);
    }

    public function testWriteSecondsDuration(): void
    {
        $interval = new DateInterval('PT45S');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('PT45S', $result);
    }

    public function testWriteHoursAndMinutes(): void
    {
        $interval = new DateInterval('PT1H30M');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('PT1H30M', $result);
    }

    public function testWriteFullTimeDuration(): void
    {
        $interval = new DateInterval('PT1H30M45S');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('PT1H30M45S', $result);
    }

    public function testWriteDaysDuration(): void
    {
        $interval = new DateInterval('P3D');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P3D', $result);
    }

    public function testWriteWeeksDuration(): void
    {
        $interval = new DateInterval('P2W');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P2W', $result);
    }

    public function testWriteWeeksDurationFromDays(): void
    {
        // Create a 14-day interval which should be formatted as 2 weeks
        $interval = new DateInterval('P14D');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P2W', $result);
    }

    public function testWriteComplexDuration(): void
    {
        $interval = new DateInterval('P1Y2M3DT4H5M6S');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P1Y2M3DT4H5M6S', $result);
    }

    public function testWriteNegativeDuration(): void
    {
        $interval = new DateInterval('PT1H30M');
        $interval->invert = 1; // Make it negative
        $result = $this->writer->write($interval);
        
        $this->assertEquals('-PT1H30M', $result);
    }

    public function testWriteDaysAndTime(): void
    {
        $interval = new DateInterval('P1DT2H30M');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P1DT2H30M', $result);
    }

    public function testWriteYearsAndMonths(): void
    {
        $interval = new DateInterval('P2Y6M');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P2Y6M', $result);
    }

    public function testWriteOnlyDatePart(): void
    {
        $interval = new DateInterval('P1Y2M3D');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P1Y2M3D', $result);
    }

    public function testWriteOnlyTimePart(): void
    {
        $interval = new DateInterval('PT4H5M6S');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('PT4H5M6S', $result);
    }

    public function testWriteZeroDuration(): void
    {
        $interval = new DateInterval('PT0S');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P', $result);
    }

    public function testWriteLargeDuration(): void
    {
        $interval = new DateInterval('P1Y2M3W4DT5H6M7S');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P1Y2M25DT5H6M7S', $result); // 3W + 4D = 25D
    }

    public function testWriteMixedPrecision(): void
    {
        $interval = new DateInterval('P0Y0M0DT0H0M0S');
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P', $result); // Should normalize to minimal form
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DurationWriter expects DateInterval or string, got array');
        
        $this->writer->write(['P' => '1H']);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DurationWriter expects DateInterval or string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DurationWriter expects DateInterval or string, got array');
        
        $this->writer->write([]);
    }

    public function testWriteInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DurationWriter expects DateInterval or string, got integer');
        
        $this->writer->write(3600);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('DURATION', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $interval = new DateInterval('PT1H');
        $mockInterval = $this->createMock(DateInterval::class);
        
        $this->assertTrue($this->writer->canWrite($interval));
        $this->assertTrue($this->writer->canWrite($mockInterval));
        $this->assertTrue($this->writer->canWrite('PT1H'));
        
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(3600));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidDurationFormat(): void
    {
        $testIntervals = [
            'PT1H',
            'PT30M',
            'PT45S',
            'P1D',
            'P1W',
            'P1Y2M3DT4H5M6S',
        ];
        
        foreach ($testIntervals as $intervalSpec) {
            $interval = new DateInterval($intervalSpec);
            $result = $this->writer->write($interval);
            
            // Should start with P (or -P for negative)
            $this->assertMatchesRegularExpression('/^-?P/', $result);
            
            // Should contain valid duration components
            $this->assertMatchesRegularExpression('/^[+-]?P[0-9YMDTHMSW]+$/', $result);
        }
    }

    public function testWriteWeekConversion(): void
    {
        // Test various day values that should convert to weeks
        $testCases = [
            [7, 'P1W'],
            [14, 'P2W'],
            [21, 'P3W'],
            [28, 'P4W'],
            [35, 'P5W'],
        ];
        
        foreach ($testCases as [$days, $expected]) {
            $interval = new DateInterval("P{$days}D");
            $result = $this->writer->write($interval);
            
            $this->assertEquals($expected, $result);
        }
    }

    public function testWriteNonWeekConversion(): void
    {
        // Test day values that should NOT convert to weeks
        $testCases = [
            1, 2, 3, 4, 5, 6,        // Less than 7 days
            8, 9, 10, 11, 12, 13,   // More than 7 but not divisible by 7
            15, 16, 17, 18, 19, 20, // etc.
        ];
        
        foreach ($testCases as $days) {
            $interval = new DateInterval("P{$days}D");
            $result = $this->writer->write($interval);
            
            // Check if it's a week conversion (7, 14, 21, 28, 35 days)
            if ($days >= 7 && $days % 7 === 0) {
                // Should be converted to weeks
                $expectedWeeks = $days / 7;
                $this->assertEquals("P{$expectedWeeks}W", $result);
            } else {
                // Should contain D for days
                $this->assertStringContainsString('D', $result);
            }
        }
    }

    public function testWriteTimezoneDetection(): void
    {
        // Test that time component is only included when needed
        $noTime = new DateInterval('P1D');
        $withTime = new DateInterval('P1DT1H');
        $onlyTime = new DateInterval('PT1H');
        
        $result1 = $this->writer->write($noTime);
        $result2 = $this->writer->write($withTime);
        $result3 = $this->writer->write($onlyTime);
        
        $this->assertStringNotContainsString('T', $result1); // No time component
        $this->assertStringContainsString('T', $result2);    // Has time component
        $this->assertStringContainsString('T', $result3);    // Only time component
    }

    public function testWriteZeroComponentHandling(): void
    {
        $zeroSeconds = new DateInterval('PT0S');
        $zeroHour = new DateInterval('PT0H');
        $zeroDay = new DateInterval('P0D');
        
        $result1 = $this->writer->write($zeroSeconds);
        $result2 = $this->writer->write($zeroHour);
        $result3 = $this->writer->write($zeroDay);
        
        // Zero durations should result in just 'P'
        $this->assertEquals('P', $result1);
        $this->assertEquals('P', $result2);
        $this->assertEquals('P', $result3);
    }

    public function testWriteNegativeDurations(): void
    {
        $positive = new DateInterval('PT1H30M');
        $negative = new DateInterval('PT1H30M');
        $negative->invert = 1;
        
        $positiveResult = $this->writer->write($positive);
        $negativeResult = $this->writer->write($negative);
        
        $this->assertEquals('PT1H30M', $positiveResult);
        $this->assertEquals('-PT1H30M', $negativeResult);
    }

    public function testWriteComponentOrder(): void
    {
        $interval = new DateInterval('P1Y2M3DT4H5M6S');
        $result = $this->writer->write($interval);
        
        // Should maintain proper order: Y M D T H M S
        $expectedOrder = ['Y', 'M', 'D', 'T', 'H', 'M', 'S'];
        $pattern = '/^-?P([0-9]+Y)?([0-9]+M)?([0-9]+D)?(T([0-9]+H)?([0-9]+M)?([0-9]+S)?)?$/';
        
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    public function testWriteRealWorldExamples(): void
    {
        $realWorldDurations = [
            'PT1H' => '1 hour meeting',
            'PT2H' => '2 hour event',
            'P1D' => '1 day duration',
            'P1W' => '1 week duration',
            'PT30M' => '30 minute appointment',
            'PT15M' => '15 minute break',
            'P1Y' => '1 year recurrence',
            'PT5H' => '5 hour travel time',
        ];
        
        foreach ($realWorldDurations as $spec => $description) {
            $interval = new DateInterval($spec);
            $result = $this->writer->write($interval);
            
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
            $this->assertMatchesRegularExpression('/^-?P[0-9YMDTHMSW]+$/', $result);
        }
    }

    public function testWritePerformance(): void
    {
        $iterations = 1000;
        $interval = new DateInterval('PT1H30M');
        
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->writer->write($interval);
        }
        $end = microtime(true);
        
        // Should be reasonably fast
        $this->assertLessThan(0.1, $end - $start);
    }

    public function testWriteCustomInterval(): void
    {
        // Test with manually constructed DateInterval
        $interval = new DateInterval('PT0S');
        $interval->y = 1;
        $interval->m = 6;
        $interval->d = 15;
        $interval->h = 8;
        $interval->i = 30;
        $interval->s = 45;
        
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P1Y6M15DT8H30M45S', $result);
    }

    public function testWriteOnlyRequiredComponents(): void
    {
        // Test that only non-zero components are included
        $interval = new DateInterval('PT0S');
        $interval->y = 0;  // Should not appear
        $interval->m = 2;  // Should appear
        $interval->d = 0;  // Should not appear
        $interval->h = 1;  // Should appear
        $interval->i = 0;  // Should not appear
        $interval->s = 30; // Should appear
        
        $result = $this->writer->write($interval);
        
        $this->assertEquals('P2MT1H30S', $result);
    }

    public function testWriteWeeksWithTimeExcluded(): void
    {
        // Weeks with time components should not be converted to weeks
        $interval = new DateInterval('P14DT1H'); // 14 days + 1 hour
        $result = $this->writer->write($interval);
        
        // Should not convert to weeks because there's a time component
        $this->assertStringNotContainsString('W', $result);
        $this->assertStringContainsString('D', $result);
        $this->assertEquals('P14DT1H', $result);
    }

    public function testWriteIntervalCreation(): void
    {
        // Test various ways to create DateInterval
        $interval1 = new DateInterval('PT1H');
        $interval2 = DateInterval::createFromDateString('1 hour');
        $interval3 = DateInterval::createFromDateString('30 minutes');
        
        $methods = [$interval1, $interval2, $interval3];
        
        foreach ($methods as $interval) {
            $result = $this->writer->write($interval);
            
            $this->assertIsString($result);
            $this->assertMatchesRegularExpression('/^-?P[0-9YMDTHMSW]+$/', $result);
        }
    }

    public function testPrivateMethodAccess()
    {
        // This test ensures our private method coverage detection works
        $reflection = new \ReflectionClass(DurationWriter::class);
        $method = $reflection->getMethod('formatInterval');
        $method->setAccessible(true);
        
        $interval = new DateInterval('PT1H');
        $result = $method->invoke($this->writer, $interval);
        
        $this->assertEquals('PT1H', $result);
    }
}
