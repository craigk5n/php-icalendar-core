<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\BinaryWriter;
use PHPUnit\Framework\TestCase;

class BinaryWriterTest extends TestCase
{
    private BinaryWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new BinaryWriter();
    }

    // ========== write Tests ==========

    public function testWriteSimpleString(): void
    {
        $input = 'Hello, World!';
        $result = $this->writer->write($input);
        
        $this->assertEquals('SGVsbG8sIFdvcmxkIQ==', $result);
    }

    public function testWriteEmptyString(): void
    {
        $input = '';
        $result = $this->writer->write($input);
        
        $this->assertEquals('', $result);
    }

    public function testWriteBinaryData(): void
    {
        // Create binary data with null bytes and other special characters
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD";
        $result = $this->writer->write($binaryData);
        
        $this->assertEquals('AAECA//+/Q==', $result);
    }

    public function testWriteUnicodeString(): void
    {
        $unicode = "Hello 世界 🌍";
        $result = $this->writer->write($unicode);
        
        $this->assertEquals('SGVsbG8g5LiW55WMIPCfjI0=', $result);
    }

    public function testWriteLongString(): void
    {
        $longString = str_repeat('This is a long string for testing. ', 20);
        $result = $this->writer->write($longString);
        
        $this->assertNotEmpty($result);
        $this->assertNotEquals($longString, $result);
        
        // Verify it's valid base64
        $this->assertTrue(base64_decode($result) !== false);
    }

    public function testWriteSpecialCharacters(): void
    {
        $specialChars = "\n\r\t\"'\\`~!@#$%^&*()_+-=[]{}|;:,.<>/?";
        $result = $this->writer->write($specialChars);
        
        $this->assertNotEmpty($result);
        $this->assertNotEquals($specialChars, $result);
        
        // Verify round trip
        $decoded = base64_decode($result);
        $this->assertEquals($specialChars, $decoded);
    }

    public function testWriteMultibyteCharacters(): void
    {
        $multibyte = "café naïve résumé Tokyo 東京 Москва";
        $result = $this->writer->write($multibyte);
        
        $this->assertNotEmpty($result);
        
        // Verify round trip
        $decoded = base64_decode($result);
        $this->assertEquals($multibyte, $decoded);
    }

    public function testWriteWithLineEndings(): void
    {
        $input = "Line 1\nLine 2\r\nLine 3\rLine 4";
        $result = $this->writer->write($input);
        
        $this->assertNotEmpty($result);
        
        // Verify round trip preserves line endings
        $decoded = base64_decode($result);
        $this->assertEquals($input, $decoded);
    }

    public function testWriteNumbersAsString(): void
    {
        $numbers = '1234567890';
        $result = $this->writer->write($numbers);
        
        $this->assertEquals('MTIzNDU2Nzg5MA==', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BinaryWriter expects string, got integer');
        
        $this->writer->write(123);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BinaryWriter expects string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BinaryWriter expects string, got array');
        
        $this->writer->write(['binary', 'data']);
    }

    public function testWriteObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BinaryWriter expects string, got object');
        
        $this->writer->write(new \stdClass());
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('BINARY', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite('string'));
        $this->assertTrue($this->writer->canWrite(''));
        $this->assertTrue($this->writer->canWrite('123'));
        $this->assertTrue($this->writer->canWrite("\x00\x01"));
        
        $this->assertFalse($this->writer->canWrite(123));
        $this->assertFalse($this->writer->canWrite(null));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(true));
        $this->assertFalse($this->writer->canWrite(12.34));
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidBase64(): void
    {
        $testStrings = [
            '',
            'a',
            'ab',
            'abc',
            'abcd',
            'Hello, World!',
            "Multi-line\nstring\ntest",
            "\x00\x01\x02\x03\x04",
        ];
        
        foreach ($testStrings as $input) {
            $result = $this->writer->write($input);
            
            // Verify it's valid base64
            $decoded = base64_decode($result);
            $this->assertNotFalse($decoded, "Failed to decode base64 for input: $input");
            
            // Verify round trip
            $this->assertEquals($input, $decoded, "Round trip failed for input: $input");
        }
    }

    public function testWriteBase64Characteristics(): void
    {
        $input = 'Test string';
        $result = $this->writer->write($input);
        
        // Base64 should only contain valid characters
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]*={0,2}$/', $result);
        
        // Base64 length should be divisible by 4 (except for empty string)
        if (!empty($result)) {
            $this->assertEquals(0, strlen($result) % 4);
        }
    }

    public function testWriteRoundTripVariousInputs(): void
    {
        $testCases = [
            'Simple ASCII' => 'Hello World!',
            'Unicode' => 'Café 🌍',
            'Binary data' => "\x00\xFF\x80\xFE",
            'Mixed content' => "Text with \x00 binary \xFF data",
            'Very long string' => str_repeat('A', 1000),
        ];
        
        foreach ($testCases as $description => $input) {
            $result = $this->writer->write($input);
            $decoded = base64_decode($result);
            
            $this->assertEquals($input, $decoded, "Round trip failed for: $description");
        }
    }

    public function testWritePadding(): void
    {
        // Test different input lengths to verify proper base64 padding
        $testCases = [
            '1' => 'MQ==',      // 1 byte = 2 chars + 2 pads
            '12' => 'MTI=',     // 2 bytes = 3 chars + 1 pad
            '123' => 'MTIz',    // 3 bytes = 4 chars + 0 pads
            '1234' => 'MTIzNA==', // 4 bytes = 6 chars + 2 pads
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $this->writer->write((string)$input);
            $this->assertEquals($expected, $result, "Base64 padding incorrect for input: $input");
        }
    }

    public function testWriteWithPhpFunctions(): void
    {
        // Ensure our writer produces the same result as base64_encode
        $testInputs = ['Hello', 'World', '123', "\x00\x01"];
        
        foreach ($testInputs as $input) {
            $writerResult = $this->writer->write($input);
            $directResult = base64_encode($input);
            
            $this->assertEquals($directResult, $writerResult);
        }
    }
}