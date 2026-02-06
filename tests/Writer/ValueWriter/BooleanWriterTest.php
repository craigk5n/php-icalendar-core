<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\BooleanWriter;
use PHPUnit\Framework\TestCase;

class BooleanWriterTest extends TestCase
{
    private BooleanWriter $writer;

    protected function setUp(): void
    {
        $this->writer = new BooleanWriter();
    }

    // ========== write Tests ==========

    public function testWriteTrue(): void
    {
        $result = $this->writer->write(true);
        
        $this->assertEquals('TRUE', $result);
    }

    public function testWriteFalse(): void
    {
        $result = $this->writer->write(false);
        
        $this->assertEquals('FALSE', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got string');
        
        $this->writer->write('true');
    }

    public function testWriteStringTrue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got string');
        
        $this->writer->write('true');
    }

    public function testWriteStringFalse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got string');
        
        $this->writer->write('false');
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got integer');
        
        $this->writer->write(1);
    }

    public function testWriteZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got integer');
        
        $this->writer->write(0);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got array');
        
        $this->writer->write([true]);
    }

    public function testWriteObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got object');
        
        $this->writer->write(new \stdClass());
    }

    public function testWriteFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BooleanWriter expects bool, got double');
        
        $this->writer->write(1.0);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('BOOLEAN', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite(true));
        $this->assertTrue($this->writer->canWrite(false));
        
        $this->assertFalse($this->writer->canWrite('true'));
        $this->assertFalse($this->writer->canWrite('false'));
        $this->assertFalse($this->writer->canWrite('TRUE'));
        $this->assertFalse($this->writer->canWrite('FALSE'));
        $this->assertFalse($this->writer->canWrite(1));
        $this->assertFalse($this->writer->canWrite(0));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(1.0));
        $this->assertFalse($this->writer->canWrite(0.0));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesUpperCase(): void
    {
        $trueResult = $this->writer->write(true);
        $falseResult = $this->writer->write(false);
        
        $this->assertEquals('TRUE', $trueResult);
        $this->assertEquals('FALSE', $falseResult);
        
        // Ensure they are uppercase
        $this->assertEquals('TRUE', strtoupper($trueResult));
        $this->assertEquals('FALSE', strtoupper($falseResult));
    }

    public function testWriteConsistency(): void
    {
        // Multiple calls should produce the same result
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals('TRUE', $this->writer->write(true));
            $this->assertEquals('FALSE', $this->writer->write(false));
        }
    }

    public function testWriteStrictBoolean(): void
    {
        // PHP's loose typing can sometimes convert values to bool
        // Make sure we only accept actual bool types
        
        $strictlyNotBool = [
            1, 0, -1,        // integers
            1.0, 0.0, -1.0, // floats
            '1', '0',       // string numbers
            'true', 'false', // string booleans
            [], [1],        // arrays
            new \stdClass(), // objects
            null,           // null
        ];
        
        foreach ($strictlyNotBool as $value) {
            $this->assertFalse($this->writer->canWrite($value), 
                "Value of type " . gettype($value) . " should not be writable");
        }
    }

    public function testWriteRoundTrip(): void
    {
        // While we can't parse back to bool directly, we can verify format
        $trueResult = $this->writer->write(true);
        $falseResult = $this->writer->write(false);
        
        // Results should be standard iCalendar boolean format
        $this->assertEquals('TRUE', $trueResult);
        $this->assertEquals('FALSE', $falseResult);
        
        // Should be valid for iCalendar format
        $this->assertMatchesRegularExpression('/^(TRUE|FALSE)$/', $trueResult);
        $this->assertMatchesRegularExpression('/^(TRUE|FALSE)$/', $falseResult);
    }

    public function testWriteEdgeCases(): void
    {
        // Test with different boolean-like values that should fail
        $edgeCases = [
            'TRUE', 'FALSE', 'true', 'false', 'True', 'False',
            '1', '0', 'yes', 'no', 'on', 'off',
            1, 0, -1, 1.0, 0.0,
        ];
        
        foreach ($edgeCases as $case) {
            $this->assertFalse($this->writer->canWrite($case), 
                "Edge case '$case' should not be writable");
        }
    }

    public function testWritePerformance(): void
    {
        // Boolean writer should be very fast
        $iterations = 1000;
        
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->writer->write($i % 2 === 0);
        }
        $end = microtime(true);
        
        // Should complete in reasonable time (less than 100ms for 1000 iterations)
        $this->assertLessThan(0.1, $end - $start);
    }

    public function testWriteMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $result = $this->writer->write($i % 2 === 0);
            unset($result);
        }
        
        $finalMemory = memory_get_usage();
        
        // Should not significantly increase memory usage
        $memoryIncrease = $finalMemory - $initialMemory;
        $this->assertLessThan(2048, $memoryIncrease); // Less than 2KB increase (allows for some system variance)
    }
}