<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\UtcOffsetWriter;
use PHPUnit\Framework\TestCase;

class UtcOffsetWriterTest extends TestCase
{
    private UtcOffsetWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new UtcOffsetWriter();
    }

    // ========== write Tests ==========

    public function testWriteZero(): void
    {
        $result = $this->writer->write(0);
        
        $this->assertEquals('+0000', $result);
    }

    public function testWritePositiveHour(): void
    {
        $result = $this->writer->write(3600); // +1 hour
        
        $this->assertEquals('+0100', $result);
    }

    public function testWriteNegativeHour(): void
    {
        $result = $this->writer->write(-3600); // -1 hour
        
        $this->assertEquals('-0100', $result);
    }

    public function testWritePositiveHourAndMinutes(): void
    {
        $result = $this->writer->write(5400); // +1 hour 30 minutes
        
        $this->assertEquals('+0130', $result);
    }

    public function testWriteNegativeHourAndMinutes(): void
    {
        $result = $this->writer->write(-5400); // -1 hour 30 minutes
        
        $this->assertEquals('-0130', $result);
    }

    public function testWriteMinutesOnly(): void
    {
        $result = $this->writer->write(1800); // +30 minutes
        
        $this->assertEquals('+0030', $result);
    }

    public function testWriteNegativeMinutesOnly(): void
    {
        $result = $this->writer->write(-1800); // -30 minutes
        
        $this->assertEquals('-0030', $result);
    }

    public function testWriteWithSeconds(): void
    {
        $result = $this->writer->write(3661); // +1 hour 1 minute 1 second
        
        $this->assertEquals('+010101', $result);
    }

    public function testWriteWithSecondsOnly(): void
    {
        $result = $this->writer->write(30); // +30 seconds
        
        $this->assertEquals('+000030', $result);
    }

    public function testWriteNegativeWithSeconds(): void
    {
        $result = $this->writer->write(-3661); // -1 hour 1 minute 1 second
        
        $this->assertEquals('-010101', $result);
    }

    public function testWriteDateInterval(): void
    {
        $interval = new \DateInterval('PT1H30M');
        $this->assertEquals('+0130', $this->writer->write($interval));

        $intervalNeg = new \DateInterval('PT2H');
        $intervalNeg->invert = 1;
        $this->assertEquals('-0200', $this->writer->write($intervalNeg));

        $intervalWithSeconds = new \DateInterval('PT1H1M1S');
        $this->assertEquals('+010101', $this->writer->write($intervalWithSeconds));
    }

    public function testWriteLargePositiveOffset(): void
    {
        $result = $this->writer->write(25200); // +7 hours
        
        $this->assertEquals('+0700', $result);
    }

    public function testWriteLargeNegativeOffset(): void
    {
        $result = $this->writer->write(-28800); // -8 hours
        
        $this->assertEquals('-0800', $result);
    }

    public function testWriteMaximumOffset(): void
    {
        $result = $this->writer->write(86400); // +24 hours
        
        $this->assertEquals('+2400', $result);
    }

    public function testWriteMinimumOffset(): void
    {
        $result = $this->writer->write(-86400); // -24 hours
        
        $this->assertEquals('-2400', $result);
    }

    public function testWriteEdgeCaseMinutes(): void
    {
        // Test values that result in exact minute boundaries
        $result = $this->writer->write(59); // +59 seconds
        $this->assertEquals('+000059', $result);
        
        $result = $this->writer->write(60); // +1 minute
        $this->assertEquals('+0001', $result);
        
        $result = $this->writer->write(3599); // +59 minutes 59 seconds
        $this->assertEquals('+005959', $result);
        
        $result = $this->writer->write(3600); // +1 hour
        $this->assertEquals('+0100', $result);
    }

    public function testWriteRealTimezoneOffsets(): void
    {
        $timezoneOffsets = [
            0 => '+0000',      // UTC
            3600 => '+0100',   // CET, BST
            7200 => '+0200',   // EET, CEST
            -18000 => '-0500', // EST
            -28800 => '-0800', // PST
            34200 => '+0930',  // Adelaide
            43200 => '+1200',  // New Zealand
            -39600 => '-1100', // Samoa
        ];
        
        foreach ($timezoneOffsets as $offset => $expected) {
            $result = $this->writer->write($offset);
            $this->assertEquals($expected, $result);
        }
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UtcOffsetWriter expects int (seconds), DateInterval or string, got array');
        
        $this->writer->write([3600]);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UtcOffsetWriter expects int (seconds), DateInterval or string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UtcOffsetWriter expects int (seconds), DateInterval or string, got array');
        
        $this->writer->write([1, 0, 0]);
    }

    public function testWriteFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UtcOffsetWriter expects int (seconds), DateInterval or string, got double');
        
        $this->writer->write(3600.5);
    }

    public function testWriteBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UtcOffsetWriter expects int (seconds), DateInterval or string, got boolean');
        
        $this->writer->write(true);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('UTC-OFFSET', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite(0));
        $this->assertTrue($this->writer->canWrite(3600));
        $this->assertTrue($this->writer->canWrite(-1800));
        $this->assertTrue($this->writer->canWrite(123456789));
        $this->assertTrue($this->writer->canWrite(-123456789));
        $this->assertTrue($this->writer->canWrite(new \DateInterval('PT1H')));
        $this->assertTrue($this->writer->canWrite('+0100'));
        
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(true));
        $this->assertFalse($this->writer->canWrite(3.14));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidUtcOffsetFormat(): void
    {
        $testOffsets = [0, 1, -1, 59, -59, 60, -60, 3599, -3599, 3600, -3600, 3661, -3661];
        
        foreach ($testOffsets as $offset) {
            $result = $this->writer->write($offset);
            
            // Should start with + or -
            $this->assertMatchesRegularExpression('/^[+-]/', $result);
            
            // Should be 5 or 7 characters (+HHMM or +HHMMSS)
            $this->assertContains(strlen($result), [5, 7]);
            
            // Should be numeric after the sign
            $numericPart = substr($result, 1);
            $this->assertMatchesRegularExpression('/^\d+$/', $numericPart);
        }
    }

    public function testWriteWithSecondsFormat(): void
    {
        // Test cases that should include seconds
        $withSeconds = [1, 30, 59, 61, 3599, 3601, 3661];
        
        foreach ($withSeconds as $offset) {
            $result = $this->writer->write($offset);
            
            if ($offset % 60 !== 0) {
                // Should include seconds
                $this->assertEquals(7, strlen($result), "Offset $offset should include seconds");
                $this->assertMatchesRegularExpression('/^[+-]\d{6}$/', $result);
            }
        }
    }

    public function testWriteWithoutSecondsFormat(): void
    {
        // Test cases that should NOT include seconds
        $withoutSeconds = [0, 60, 120, 1800, 3600, 7200, 5400];
        
        foreach ($withoutSeconds as $offset) {
            $result = $this->writer->write($offset);
            
            // Should NOT include seconds
            $this->assertEquals(5, strlen($result), "Offset $offset should not include seconds");
            $this->assertMatchesRegularExpression('/^[+-]\d{4}$/', $result);
        }
    }

    public function testWriteZeroPadding(): void
    {
        $testCases = [
            1 => '+000001',    // 1 second
            59 => '+000059',   // 59 seconds
            60 => '+0001',     // 1 minute
            3599 => '+005959', // 59 minutes 59 seconds
            3600 => '+0100',   // 1 hour
        ];
        
        foreach ($testCases as $offset => $expected) {
            $result = $this->writer->write($offset);
            $this->assertEquals($expected, $result);
        }
    }

    public function testWriteSignHandling(): void
    {
        // Test positive, zero, and negative values
        $testCases = [
            3600 => '+0100',
            0 => '+0000',
            -3600 => '-0100',
            1800 => '+0030',
            -1800 => '-0030',
            30 => '+000030',
            -30 => '-000030',
        ];
        
        foreach ($testCases as $offset => $expected) {
            $result = $this->writer->write($offset);
            $this->assertEquals($expected, $result);
        }
    }

    public function testWriteLargeValues(): void
    {
        $largeValues = [
            86400 => '+2400',      // 24 hours
            90000 => '+2500',      // 25 hours (edge case)
            -86400 => '-2400',     // -24 hours
            -90000 => '-2500',     // -25 hours (edge case)
            100000 => '+274640',   // Large value with seconds
        ];
        
        foreach ($largeValues as $offset => $expected) {
            $result = $this->writer->write($offset);
            $this->assertEquals($expected, $result);
        }
    }

    public function testWriteRoundTrip(): void
    {
        // Test some common offsets and verify they produce expected results
        $commonOffsets = [
            0,      // UTC
            3600,   // +1:00
            -3600,  // -1:00
            7200,   // +2:00
            -18000, // -5:00
            34200,  // +9:30
        ];
        
        foreach ($commonOffsets as $offset) {
            $result = $this->writer->write($offset);
            
            // Verify format is correct
            $this->assertMatchesRegularExpression('/^[+-]\d{4,7}$/', $result);
            
            // Verify sign matches input
            $expectedSign = $offset >= 0 ? '+' : '-';
            $this->assertStringStartsWith($expectedSign, $result);
        }
    }
}
