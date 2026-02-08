<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Icalendar\Writer\ValueWriter\PeriodWriter;
use PHPUnit\Framework\TestCase;

class PeriodWriterTest extends TestCase
{
    private PeriodWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new PeriodWriter();
    }

    // ========== write Tests ==========

    public function testWriteWithStartAndEnd(): void
    {
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'end' => new DateTime('2026-02-06 11:00:00', new \DateTimeZone('UTC')),
        ];
        
        $result = $this->writer->write($value);
        
        $this->assertEquals('20260206T100000Z/20260206T110000Z', $result);
    }

    public function testWriteWithStartAndDuration(): void
    {
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'duration' => new DateInterval('PT1H'),
        ];
        
        $result = $this->writer->write($value);
        
        $this->assertEquals('20260206T100000Z/PT1H', $result);
    }

    public function testWriteWithDateTimeImmutable(): void
    {
        $value = [
            'start' => new DateTimeImmutable('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'end' => new DateTimeImmutable('2026-02-06 11:00:00', new \DateTimeZone('UTC')),
        ];
        
        $result = $this->writer->write($value);
        
        $this->assertEquals('20260206T100000Z/20260206T110000Z', $result);
    }

    public function testWriteWithDurationOnly(): void
    {
        // Invalid, but let's see how it fails (needs start)
        $value = [
            'duration' => new DateInterval('PT1H'),
        ];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period start must be DateTimeInterface');
        $this->writer->write($value);
    }

    public function testWriteWithComplexDuration(): void
    {
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'duration' => new DateInterval('P1DT2H30M'),
        ];
        
        $result = $this->writer->write($value);
        
        $this->assertEquals('20260206T100000Z/P1DT2H30M', $result);
    }

    public function testWriteWithBothEndAndDuration(): void
    {
        // If both are present, end should take precedence (or as implemented)
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'end' => new DateTime('2026-02-06 11:00:00', new \DateTimeZone('UTC')),
            'duration' => new DateInterval('PT1H'),
        ];
        
        $result = $this->writer->write($value);
        
        // Based on implementation, end takes precedence
        $this->assertEquals('20260206T100000Z/20260206T110000Z', $result);
    }

    public function testWriteWithDifferentTimezones(): void
    {
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('America/New_York')),
            'end' => new DateTime('2026-02-06 11:00:00', new \DateTimeZone('America/New_York')),
        ];
        
        $result = $this->writer->write($value);
        
        $this->assertEquals('20260206T100000/20260206T110000', $result);
    }

    public function testWriteWithUTCTime(): void
    {
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'duration' => new DateInterval('PT1H'),
        ];
        
        $result = $this->writer->write($value);
        
        $this->assertStringContainsString('Z', $result);
    }

    public function testWriteInvalidStructure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Each period must be an array');

        $this->writer->write(['not-an-array']);
    }

    public function testWriteMissingStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period start must be DateTimeInterface');
        
        $this->writer->write([['end' => new DateTime()]]);
    }

    public function testWriteMissingStartInEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->writer->write([[]]);
    }

    public function testWriteStartNotDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period start must be DateTimeInterface');
        
        $this->writer->write(['start' => '2026-02-06', 'end' => new DateTime()]);
    }

    public function testWriteEndNotDateTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period must have end (DateTimeInterface) or duration (DateInterval)');
        
        $this->writer->write(['start' => new DateTime(), 'end' => '2026-02-06']);
    }

    public function testWriteDurationNotDateInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period must have end (DateTimeInterface) or duration (DateInterval)');
        
        $this->writer->write(['start' => new DateTime(), 'duration' => 'PT1H']);
    }

    public function testWriteMissingEndAndDuration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Period must have end (DateTimeInterface) or duration (DateInterval)');
        
        $this->writer->write(['start' => new DateTime()]);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PeriodWriter expects array or string, got integer');
        
        $this->writer->write(123);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PeriodWriter expects array or string, got NULL');
        
        $this->writer->write(null);
    }

    // New test for writing multiple periods
    public function testWriteMultiplePeriods(): void
    {
        $period1 = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'end' => new DateTime('2026-02-06 11:00:00', new \DateTimeZone('UTC')),
        ];
        $period2 = [
            'start' => new DateTime('2026-02-06 12:00:00', new \DateTimeZone('UTC')),
            'duration' => new DateInterval('PT30M'),
        ];

        $value = [$period1, $period2];
        $result = $this->writer->write($value);

        $expected = '20260206T100000Z/20260206T110000Z,20260206T120000Z/PT30M';
        $this->assertEquals($expected, $result);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('PERIOD', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite(['start' => new DateTime(), 'end' => new DateTime()]));
        $this->assertTrue($this->writer->canWrite([])); // Empty array is still an array
        $this->assertTrue($this->writer->canWrite('20260206T100000Z/PT1H')); // String is now supported
        $this->assertTrue($this->writer->canWrite([['start' => new DateTime(), 'end' => new DateTime()]]));
        
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite(123));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidPeriodFormat(): void
    {
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'end' => new DateTime('2026-02-06 11:00:00', new \DateTimeZone('UTC')),
        ];
        
        $result = $this->writer->write($value);
        
        // Should match pattern (start/end or start/duration)
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z?\/\d{8}T\d{6}Z?$/', $result);
    }

    public function testWriteWithDurationFormat(): void
    {
        $value = [
            'start' => new DateTime('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'duration' => new DateInterval('PT1H'),
        ];
        
        $result = $this->writer->write($value);
        
        // Should match pattern
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z?\/P[0-9YMDTHMSW]+$/', $result);
    }

    public function testWriteEdgeCases(): void
    {
        $value = [
            'start' => new DateTime('2026-01-01 00:00:00', new \DateTimeZone('UTC')),
            'end' => new DateTime('2026-12-31 23:59:59', new \DateTimeZone('UTC')),
        ];
        
        $result = $this->writer->write($value);
        $this->assertNotEmpty($result);
    }

    public function testWriteWithNullEndAndDuration(): void
    {
        $value = [
            'start' => new DateTime(),
            'end' => null,
            'duration' => null,
        ];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->writer->write($value);
    }

    public function testWriteWithVariousDateTimeFormats(): void
    {
        $value = [
            'start' => new DateTimeImmutable('2026-02-06 10:00:00', new \DateTimeZone('UTC')),
            'duration' => new DateInterval('PT1H'),
        ];
        
        $result = $this->writer->write($value);
        $this->assertStringContainsString('20260206T100000Z', $result);
        $this->assertStringContainsString('PT1H', $result);
    }
}