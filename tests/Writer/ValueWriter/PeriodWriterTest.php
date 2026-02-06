<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Icalendar\Writer\ValueWriter\PeriodWriter;
use PHPUnit\Framework\TestCase;

class PeriodWriterTest extends TestCase
{
    private PeriodWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new PeriodWriter();
    }

    // ========== write Tests ==========

    public function testWriteWithStartAndEnd(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $end = new DateTime('2026-02-06T10:00:00', new DateTimeZone('America/New_York'));
        
        $period = [
            'start' => $start,
            'end' => $end
        ];
        
        $result = $this->writer->write($period);
        
        $this->assertEquals('20260206T090000/20260206T100000', $result);
    }

    public function testWriteWithStartAndDuration(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $duration = new DateInterval('PT1H30M');
        
        $period = [
            'start' => $start,
            'duration' => $duration
        ];
        
        $result = $this->writer->write($period);
        
        $this->assertEquals('20260206T090000/PT1H30M', $result);
    }

    public function testWriteWithDateTimeImmutable(): void
    {
        $start = new DateTimeImmutable('2026-02-06T09:00:00');
        $end = new DateTimeImmutable('2026-02-06T10:30:00');
        
        $period = [
            'start' => $start,
            'end' => $end
        ];
        
        $result = $this->writer->write($period);
        
        $this->assertEquals('20260206T090000/20260206T103000', $result);
    }

    public function testWriteWithDurationOnly(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $duration = new DateInterval('P1D'); // 1 day
        
        $period = [
            'start' => $start,
            'duration' => $duration
        ];
        
        $result = $this->writer->write($period);
        
        $this->assertEquals('20260206T090000/P1D', $result);
    }

    public function testWriteWithComplexDuration(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $duration = new DateInterval('P2DT3H4M5S'); // 2 days, 3 hours, 4 minutes, 5 seconds
        
        $period = [
            'start' => $start,
            'duration' => $duration
        ];
        
        $result = $this->writer->write($period);
        
        $this->assertEquals('20260206T090000/P2DT3H4M5S', $result);
    }

    public function testWriteWithBothEndAndDuration(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $end = new DateTime('2026-02-06T10:00:00', new DateTimeZone('America/New_York'));
        $duration = new DateInterval('PT1H');
        
        $period = [
            'start' => $start,
            'end' => $end,
            'duration' => $duration
        ];
        
        $result = $this->writer->write($period);
        
        // Should use 'end' when both are provided
        $this->assertEquals('20260206T090000/20260206T100000', $result);
    }

    public function testWriteWithDifferentTimezones(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new \DateTimeZone('America/New_York'));
        $end = new DateTime('2026-02-06T10:00:00', new \DateTimeZone('America/New_York'));
        
        $period = [
            'start' => $start,
            'end' => $end
        ];
        
        $result = $this->writer->write($period);
        
        // Should use local time format
        $this->assertEquals('20260206T090000/20260206T100000', $result);
    }

    public function testWriteWithUTCTime(): void
    {
        $start = new DateTime('2026-02-06T09:00:00Z');
        $end = new DateTime('2026-02-06T10:00:00Z');
        
        $period = [
            'start' => $start,
            'end' => $end
        ];
        
        $result = $this->writer->write($period);
        
        $this->assertEquals('20260206T090000Z/20260206T100000Z', $result);
    }

    public function testWriteInvalidStructure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PeriodWriter expects array with start and end/duration');
        
        $this->writer->write(['not' => 'valid']);
    }

    public function testWriteMissingStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PeriodWriter expects array with start and end/duration');
        
        $this->writer->write(['end' => new DateTime()]);
    }

    public function testWriteMissingStartInEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PeriodWriter expects array with start and end/duration');
        
        $this->writer->write([]);
    }

    public function testWriteStartNotDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period start must be DateTimeInterface');
        
        $period = [
            'start' => '20260206T090000',
            'end' => new DateTime('2026-02-06T10:00:00', new DateTimeZone('America/New_York'))
        ];
        
        $this->writer->write($period);
    }

    public function testWriteEndNotDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period end must be DateTimeInterface');
        
        $period = [
            'start' => new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York')),
            'end' => '20260206T100000'
        ];
        
        $this->writer->write($period);
    }

    public function testWriteDurationNotDateInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period duration must be DateInterval');
        
        $period = [
            'start' => new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York')),
            'duration' => 'PT1H'
        ];
        
        $this->writer->write($period);
    }

    public function testWriteMissingEndAndDuration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period must have end or duration');
        
        $period = [
            'start' => new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'))
        ];
        
        $this->writer->write($period);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PeriodWriter expects array with start and end/duration');
        
        $this->writer->write('not an array');
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PeriodWriter expects array with start and end/duration');
        
        $this->writer->write(null);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('PERIOD', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $validPeriod = [
            'start' => new DateTime(),
            'end' => new DateTime()
        ];
        
        $startOnly = ['start' => new DateTime()];
        $emptyArray = [];
        $nonArray = 'not an array';
        $arrayWithoutStart = ['end' => new DateTime()];
        
        $this->assertTrue($this->writer->canWrite($validPeriod));
        $this->assertTrue($this->writer->canWrite($startOnly));
        
        $this->assertFalse($this->writer->canWrite($emptyArray));
        $this->assertFalse($this->writer->canWrite($nonArray));
        $this->assertFalse($this->writer->canWrite($arrayWithoutStart));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidPeriodFormat(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $end = new DateTime('2026-02-06T10:00:00', new DateTimeZone('America/New_York'));
        
        $period = ['start' => $start, 'end' => $end];
        $result = $this->writer->write($period);
        
        // Should contain exactly one slash
        $this->assertEquals(1, substr_count($result, '/'));
        
        // Should start with datetime format
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z?/', $result);
        
        // Should end with datetime or duration format
        $parts = explode('/', $result);
        $this->assertCount(2, $parts);
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z?$/', $parts[0]);
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z?$|^P[\dTYWDHMS]+$/', $parts[1]);
    }

    public function testWriteWithDurationFormat(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $duration = new DateInterval('PT1H30M');
        
        $period = ['start' => $start, 'duration' => $duration];
        $result = $this->writer->write($period);
        
        $parts = explode('/', $result);
        $this->assertMatchesRegularExpression('/^P[\dTYWDHMS]+$/', $parts[1]);
    }

    public function testWriteEdgeCases(): void
    {
        // Test with very short duration
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        $duration = new DateInterval('PT1S');
        
        $period = ['start' => $start, 'duration' => $duration];
        $result = $this->writer->write($period);
        
        $this->assertEquals('20260206T090000/PT1S', $result);
        
        // Test with very long duration
        $longDuration = new DateInterval('P1Y2M3DT4H5M6S');
        $period = ['start' => $start, 'duration' => $longDuration];
        $result = $this->writer->write($period);
        
        $this->assertStringContainsString('P1Y2M3DT4H5M6S', $result);
    }

    public function testWriteWithNullEndAndDuration(): void
    {
        $start = new DateTime('2026-02-06T09:00:00', new DateTimeZone('America/New_York'));
        
        $period = [
            'start' => $start,
            'end' => null,
            'duration' => null
        ];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period must have end or duration');
        
        $this->writer->write($period);
    }

    public function testWriteWithVariousDateTimeFormats(): void
    {
        $testCases = [
            'Basic time' => ['start' => '2026-02-06T09:00:00', 'end' => '2026-02-06T10:00:00'],
            'With seconds' => ['start' => '2026-02-06T09:00:45', 'end' => '2026-02-06T10:00:45'],
            'UTC time' => ['start' => '2026-02-06T09:00:00Z', 'end' => '2026-02-06T10:00:00Z'],
            'Midnight' => ['start' => '2026-02-06T00:00:00', 'end' => '2026-02-07T00:00:00'],
        ];
        
        foreach ($testCases as $description => $times) {
            $period = [
                'start' => new DateTime($times['start']),
                'end' => new DateTime($times['end'])
            ];
            
            $result = $this->writer->write($period);
            
            $this->assertNotEmpty($result, "Should produce output for: $description");
            $this->assertEquals(1, substr_count($result, '/'), "Should have exactly one slash for: $description");
        }
    }
}