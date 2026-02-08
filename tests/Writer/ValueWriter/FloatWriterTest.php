<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\FloatWriter;
use PHPUnit\Framework\TestCase;

class FloatWriterTest extends TestCase
{
    private FloatWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new FloatWriter();
    }

    // ========== write Tests ==========

    public function testWriteFloat(): void
    {
        $result = $this->writer->write(3.14);
        
        $this->assertEquals('3.14', $result);
    }

    public function testWriteInteger(): void
    {
        $result = $this->writer->write(42);
        
        $this->assertEquals('42', $result);
    }

    public function testWriteNegativeFloat(): void
    {
        $result = $this->writer->write(-2.71);
        
        $this->assertEquals('-2.71', $result);
    }

    public function testWriteNegativeInteger(): void
    {
        $result = $this->writer->write(-100);
        
        $this->assertEquals('-100', $result);
    }

    public function testWriteZero(): void
    {
        $result = $this->writer->write(0);
        
        $this->assertEquals('0', $result);
    }

    public function testWriteZeroFloat(): void
    {
        $result = $this->writer->write(0.0);
        
        $this->assertEquals('0', $result);
    }

    public function testWriteSmallFloat(): void
    {
        $result = $this->writer->write(0.001);
        
        $this->assertEquals('0.001', $result);
    }

    public function testWriteLargeFloat(): void
    {
        $result = $this->writer->write(1234567.89);
        
        $this->assertEquals('1234567.89', $result);
    }

    public function testWriteWithTrailingZeros(): void
    {
        $result = $this->writer->write(3.1400);
        
        $this->assertEquals('3.14', $result);
    }

    public function testWriteWithTrailingZeroDecimal(): void
    {
        $result = $this->writer->write(5.0);
        
        $this->assertEquals('5', $result);
    }

    public function testWriteWithVerySmallDecimal(): void
    {
        $result = $this->writer->write(1.0000001);
        
        $this->assertEquals('1.0000001', $result);
    }

    public function testWriteScientificNotationInput(): void
    {
        // Test input that might be in scientific notation
        $result = $this->writer->write(1.5e3);
        
        $this->assertEquals('1500', $result);
    }

    public function testWriteVeryLargeNumber(): void
    {
        $result = $this->writer->write(1.5e8);
        
        $this->assertEquals('150000000', $result);
    }

    public function testWriteVerySmallNumber(): void
    {
        $result = $this->writer->write(1.5e-6);
        
        $this->assertEquals('0.0000015', $result);
    }

    public function testWritePrecisionEdgeCase(): void
    {
        // Test numbers that might have precision issues
        $result = $this->writer->write(0.1 + 0.2); // Often results in 0.30000000000000004
        
        $this->assertEquals('0.3', $result);
    }

    public function testWriteNegativeZero(): void
    {
        $result = $this->writer->write(-0.0);
        
        $this->assertEquals('0', $result);
    }

    public function testWriteInfinity(): void
    {
        $result = $this->writer->write(INF);
        
        $this->assertEquals('INF', $result);
    }

    public function testWriteNegativeInfinity(): void
    {
        $result = $this->writer->write(-INF);
        
        $this->assertEquals('-INF', $result);
    }

    public function testWriteNaN(): void
    {
        $result = $this->writer->write(NAN);
        
        $this->assertEquals('NAN', $result);
    }

    public function testWriteMaxFloat(): void
    {
        $result = $this->writer->write(PHP_FLOAT_MAX);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testWriteMinFloat(): void
    {
        $result = $this->writer->write(PHP_FLOAT_MIN);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FloatWriter expects float or int, got string');
        
        $this->writer->write('3.14');
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FloatWriter expects float or int, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FloatWriter expects float or int, got array');
        
        $this->writer->write([3.14]);
    }

    public function testWriteObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FloatWriter expects float or int, got object');
        
        $this->writer->write(new \stdClass());
    }

    public function testWriteBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FloatWriter expects float or int, got boolean');
        
        $this->writer->write(true);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('FLOAT', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite(3.14));
        $this->assertTrue($this->writer->canWrite(42));
        $this->assertTrue($this->writer->canWrite(-2.71));
        $this->assertTrue($this->writer->canWrite(0));
        $this->assertTrue($this->writer->canWrite(0.0));
        
        $this->assertFalse($this->writer->canWrite('3.14'));
        $this->assertFalse($this->writer->canWrite('42'));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(true));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidFormat(): void
    {
        $testValues = [0, 1, -1, 3.14, -2.71, 0.001, 123456.789];
        
        foreach ($testValues as $value) {
            $result = $this->writer->write($value);
            
            // Should be numeric string
            $this->assertIsString($result);
            $this->assertMatchesRegularExpression('/^-?\d+\.?\d*$/', $result);
        }
    }

    public function testWriteConsistentOutput(): void
    {
        $testValues = [3.14, 42, 0, -2.71];
        
        foreach ($testValues as $value) {
            $result1 = $this->writer->write($value);
            $result2 = $this->writer->write($value);
            
            $this->assertEquals($result1, $result2, "Output should be consistent for value: $value");
        }
    }

    public function testWriteIntegerHandling(): void
    {
        $integers = [0, 1, 42, 100, -1, -100, PHP_INT_MAX, PHP_INT_MIN];
        
        foreach ($integers as $int) {
            $result = $this->writer->write($int);
            
            // Integers should not have decimal points
            $this->assertStringNotContainsString('.', $result, "Integer $int should not have decimal point");
            $this->assertEquals((string) $int, $result);
        }
    }

    public function testWriteFloatFormatting(): void
    {
        $testCases = [
            [3.0, '3'],
            [3.1, '3.1'],
            [3.10, '3.1'],
            [3.100, '3.1'],
            [3.14159, '3.14159'],
            [0.0, '0'],
            [0.1, '0.1'],
            [0.10, '0.1'],
            [0.100, '0.1'],
        ];
        
        foreach ($testCases as [$input, $expected]) {
            $result = $this->writer->write($input);
            $this->assertEquals($expected, $result, "Input $input should format as $expected");
        }
    }

    public function testWriteEdgeCaseNumbers(): void
    {
        $edgeCases = [
            0.0000001,      // Very small
            999999999.999,  // Very large with decimals
            1e-10,          // Scientific notation small
            1e10,           // Scientific notation large
            -0.000001,      // Very small negative
            -999999999.999, // Very large negative with decimals
        ];
        
        foreach ($edgeCases as $value) {
            $result = $this->writer->write($value);
            
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
            $this->assertMatchesRegularExpression('/^-?\d+\.?\d*$/', $result);
        }
    }

    public function testWritePrecisionMaintainance(): void
    {
        // Test that reasonable precision is maintained
        $testValues = [
            0.123456789,
            3.14159265359,
            2.71828182846,
        ];
        
        foreach ($testValues as $value) {
            $result = $this->writer->write($value);
            $parsed = (float) $result;
            
            // Should be reasonably close to original
            $this->assertEqualsWithDelta($value, $parsed, 0.000001, 
                "Precision should be maintained for $value");
        }
    }

    public function testWriteNoScientificNotation(): void
    {
        // Test that output never uses scientific notation
        $largeNumbers = [
            1e6,     // 1 million
            1e9,     // 1 billion
            1.5e8,   // 150 million
            1.234e7, // 12.34 million
        ];
        
        foreach ($largeNumbers as $value) {
            $result = $this->writer->write($value);
            
            // Should not contain 'e' or 'E'
            $this->assertStringNotContainsString('e', $result);
            $this->assertStringNotContainsString('E', $result);
            
            // Should be decimal notation
            $this->assertMatchesRegularExpression('/^\d+$/', $result);
        }
    }

    public function testWritePerformance(): void
    {
        $iterations = 1000;
        $values = [3.14, 42, 0, -2.71];
        
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $value = $values[$i % count($values)];
            $this->writer->write($value);
        }
        $end = microtime(true);
        
        // Should be reasonably fast
        $this->assertLessThan(0.1, $end - $start);
    }

    public function testWriteRealWorldExamples(): void
    {
        $realWorldValues = [
            3.14159,        // Pi approximation
            2.71828,        // E approximation
            1.61803,        // Golden ratio
            9.8,            // Gravity
            299792458,      // Speed of light
            6.62607015e-34, // Planck constant (will be formatted)
        ];
        
        foreach ($realWorldValues as $value) {
            $result = $this->writer->write($value);
            
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
            $this->assertMatchesRegularExpression('/^-?\d+\.?\d*$/', $result);
        }
    }
}