<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\IntegerWriter;
use PHPUnit\Framework\TestCase;

class IntegerWriterTest extends TestCase
{
    private IntegerWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new IntegerWriter();
    }

    // ========== write Tests ==========

    public function testWritePositiveInteger(): void
    {
        $result = $this->writer->write(42);
        
        $this->assertEquals('42', $result);
    }

    public function testWriteNegativeInteger(): void
    {
        $result = $this->writer->write(-17);
        
        $this->assertEquals('-17', $result);
    }

    public function testWriteZero(): void
    {
        $result = $this->writer->write(0);
        
        $this->assertEquals('0', $result);
    }

    public function testWriteLargeInteger(): void
    {
        $result = $this->writer->write(999999999);
        
        $this->assertEquals('999999999', $result);
    }

    public function testWriteVeryLargeInteger(): void
    {
        $result = $this->writer->write(PHP_INT_MAX);
        
        $this->assertEquals((string) PHP_INT_MAX, $result);
    }

    public function testWriteVerySmallInteger(): void
    {
        $result = $this->writer->write(PHP_INT_MIN);
        
        $this->assertEquals((string) PHP_INT_MIN, $result);
    }

    public function testWriteSingleDigit(): void
    {
        for ($i = 0; $i <= 9; $i++) {
            $result = $this->writer->write($i);
            $this->assertEquals((string) $i, $result);
        }
    }

    public function testWriteNegativeZero(): void
    {
        // -0 is just 0 in PHP
        $result = $this->writer->write(-0);
        
        $this->assertEquals('0', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerWriter expects int or string, got array');
        
        $this->writer->write([42]);
    }

    public function testWriteFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerWriter expects int or string, got double');
        
        $this->writer->write(42.0);
    }

    public function testWriteNumericString(): void
    {
        // Now strings are supported
        $result = $this->writer->write('123');
        $this->assertEquals('123', $result);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerWriter expects int or string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerWriter expects int or string, got array');
        
        $this->writer->write([42]);
    }

    public function testWriteObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerWriter expects int or string, got object');
        
        $this->writer->write(new \stdClass());
    }

    public function testWriteBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerWriter expects int or string, got boolean');
        
        $this->writer->write(true);
    }

    public function testWriteFalseBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('IntegerWriter expects int or string, got boolean');
        
        $this->writer->write(false);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('INTEGER', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite(42));
        $this->assertTrue($this->writer->canWrite(-17));
        $this->assertTrue($this->writer->canWrite(0));
        $this->assertTrue($this->writer->canWrite(PHP_INT_MAX));
        $this->assertTrue($this->writer->canWrite(PHP_INT_MIN));
        $this->assertTrue($this->writer->canWrite('42'));
        
        $this->assertFalse($this->writer->canWrite(42.0));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(true));
        $this->assertFalse($this->writer->canWrite(false));
        $this->assertFalse($this->writer->canWrite(42.5));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesStringRepresentation(): void
    {
        $testIntegers = [-12345, -1, 0, 1, 12345, PHP_INT_MAX, PHP_INT_MIN];
        
        foreach ($testIntegers as $integer) {
            $result = $this->writer->write($integer);
            
            $this->assertIsString($result);
            $this->assertEquals((string) $integer, $result);
            
            // Verify it can be converted back to the same integer
            $backToInt = (int) $result;
            $this->assertEquals($integer, $backToInt);
        }
    }

    public function testWriteProducesValidNumericString(): void
    {
        $testIntegers = [-100, -1, 0, 1, 100];
        
        foreach ($testIntegers as $integer) {
            $result = $this->writer->write($integer);
            
            $this->assertIsString($result);
            $this->assertEquals((string) $integer, $result);
            
            // Should match numeric string pattern
            $this->assertMatchesRegularExpression('/^-?\d+$/', $result);
        }
    }

    public function testWriteConsistency(): void
    {
        $testIntegers = [0, 1, -1, 42, -999];
        
        foreach ($testIntegers as $integer) {
            $result1 = $this->writer->write($integer);
            $result2 = $this->writer->write($integer);
            
            $this->assertEquals($result1, $result2, "Output should be consistent for $integer");
        }
    }

    public function testWriteEdgeCases(): void
    {
        $edgeCases = [
            0,
            1,
            -1,
            PHP_INT_MAX,
            PHP_INT_MIN,
            999999999,
            -999999999,
        ];
        
        foreach ($edgeCases as $case) {
            $result = $this->writer->write($case);
            
            $this->assertIsString($result);
            $this->assertEquals((string) $case, $result);
            
            // Should be exactly the string representation
            $expected = strval($case);
            $this->assertEquals($expected, $result);
        }
    }

    public function testWritePerformance(): void
    {
        $iterations = 10000;
        $testIntegers = [0, 1, -1, 42, 999];
        
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $integer = $testIntegers[$i % count($testIntegers)];
            $this->writer->write($integer);
        }
        $end = microtime(true);
        
        // Should be very fast (simple type casting)
        $this->assertLessThan(0.05, $end - $start);
    }

    public function testWriteMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->writer->write($i);
            unset($result);
        }
        
        $finalMemory = memory_get_usage();
        
        $this->assertGreaterThanOrEqual($initialMemory, $finalMemory);
    }

    public function testWriteRoundTrip(): void
    {
        $testIntegers = [-123, -1, 0, 1, 456, 789];
        
        foreach ($testIntegers as $original) {
            $stringResult = $this->writer->write($original);
            $backToInt = (int) $stringResult;
            
            $this->assertEquals($original, $backToInt, 
                "Round trip failed for $original -> $stringResult -> $backToInt");
        }
    }

    public function testWriteRealWorldIntegers(): void
    {
        $realWorldIntegers = [
            2026,        // Year
            2,           // Month
            14,          // Day
            9,           // Hour
            30,          // Minute
            0,           // Second
            -5,          // UTC offset hours
            30,          // UTC offset minutes
            1,           // Sequence number
            0,           // Priority
        ];
        
        foreach ($realWorldIntegers as $integer) {
            $result = $this->writer->write($integer);
            
            $this->assertIsString($result);
            $this->assertEquals((string) $integer, $result);
            $this->assertMatchesRegularExpression('/^-?\d+$/', $result);
        }
    }



    public function testWriteStringComparison(): void
    {
        // Ensure our output matches PHP's string casting
        $testIntegers = [0, 1, -1, 42, -999, PHP_INT_MAX, PHP_INT_MIN];
        
        foreach ($testIntegers as $integer) {
            $writerResult = $this->writer->write($integer);
            $stringCast = (string) $integer;
            
            $this->assertEquals($stringCast, $writerResult, 
                "Writer result should match string casting for $integer");
        }
    }

    public function testWriteStrictIntegerCheck(): void
    {
        // Test that only actual integers or strings are accepted
        $nonIntegers = [
            42.0,      // Float
            42.5,      // Float with decimal
            true,      // Boolean
            false,     // Boolean
            null,      // Null
            [],        // Array
            new \stdClass(), // Object
        ];
        
        foreach ($nonIntegers as $value) {
            $this->assertFalse($this->writer->canWrite($value), 
                "Non-integer value of type " . gettype($value) . " should not be writable");
        }
    }
}