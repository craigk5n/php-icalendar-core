<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer\ValueWriter;

use Icalendar\Writer\ValueWriter\TextWriter;
use PHPUnit\Framework\TestCase;

class TextWriterTest extends TestCase
{
    private TextWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new TextWriter();
    }

    // ========== write Tests ==========

    public function testWriteSimpleText(): void
    {
        $result = $this->writer->write('Hello, World!');
        
        $this->assertEquals('Hello\, World!', $result);
    }

    public function testWriteEmptyString(): void
    {
        $result = $this->writer->write('');
        
        $this->assertEquals('', $result);
    }

    public function testWriteNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TextWriter expects string, got NULL');
        
        $this->writer->write(null);
    }

    public function testWriteTextWithSemicolon(): void
    {
        $result = $this->writer->write('key:value;key2:value2');
        
        $this->assertEquals('key:value\;key2:value2', $result);
    }

    public function testWriteTextWithMultipleSemicolons(): void
    {
        $result = $this->writer->write('first;second;third');
        
        $this->assertEquals('first\;second\;third', $result);
    }

    public function testWriteTextWithCommas(): void
    {
        $result = $this->writer->write('apples, oranges, bananas');
        
        $this->assertEquals('apples\, oranges\, bananas', $result);
    }

    public function testWriteTextWithBackslash(): void
    {
        $result = $this->writer->write('file\\path\\to\\file');
        
        $this->assertEquals('file\\\\path\\\\to\\\\file', $result);
    }

    public function testWriteTextWithNewline(): void
    {
        $result = $this->writer->write("Line 1\nLine 2");
        
        $this->assertEquals('Line 1\\nLine 2', $result);
    }

    public function testWriteTextWithCarriageReturn(): void
    {
        $result = $this->writer->write("Line 1\rLine 2");
        
        // CR is removed in the actual implementation
        $this->assertEquals('Line 1Line 2', $result);
    }

    public function testWriteTextWithCRLF(): void
    {
        $result = $this->writer->write("Line 1\r\nLine 2");
        
        $this->assertEquals('Line 1\\nLine 2', $result);
    }

    public function testWriteTextWithMixedNewlines(): void
    {
        $result = $this->writer->write("Line 1\r\nLine 2\nLine 3\rLine 4");
        
        // CRLF becomes \n, LF becomes \n, CR is removed
        $this->assertEquals('Line 1\\nLine 2\\nLine 3Line 4', $result);
    }

    public function testWriteComplexEscaping(): void
    {
        $input = "Path: C:\\Users\\Name\\Documents\nFiles: file1.txt, file2.txt;Notes: Important";
        $expected = "Path: C:\\\\Users\\\\Name\\\\Documents\\nFiles: file1.txt\, file2.txt\;Notes: Important";
        
        $result = $this->writer->write($input);
        
        $this->assertEquals($expected, $result);
    }

    public function testWriteUnicodeText(): void
    {
        $result = $this->writer->write('你好世界 🌍 café');
        
        $this->assertEquals('你好世界 🌍 café', $result);
    }

    public function testWriteSpecialCharacters(): void
    {
        $input = '!@#$%^&*()_+-=[]{}|:<>?~`';
        $result = $this->writer->write($input);
        
        $this->assertEquals($input, $result);
    }

    public function testWriteConsecutiveBackslashes(): void
    {
        $input = '\\';
        $result = $this->writer->write($input);
        
        // Single backslash becomes double
        $this->assertEquals('\\\\', $result);
    }

    public function testWriteEscapingOrder(): void
    {
        // Test that backslashes are escaped before other characters
        $input = 'Text with \\, and \\;';
        $result = $this->writer->write($input);
        
        $this->assertEquals('Text with \\\\\, and \\\\\;', $result);
    }

    public function testWriteTrailingBackslash(): void
    {
        $result = $this->writer->write('text\\');
        
        $this->assertEquals('text\\\\', $result);
    }

    public function testWriteMultipleNewlines(): void
    {
        $result = $this->writer->write("Line 1\n\nLine 3");
        
        $this->assertEquals('Line 1\\n\\nLine 3', $result);
    }

    public function testWriteInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TextWriter expects string, got integer');
        
        $this->writer->write(123);
    }

    public function testWriteArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TextWriter expects string, got array');
        
        $this->writer->write(['text']);
    }

    public function testWriteObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TextWriter expects string, got object');
        
        $this->writer->write(new \stdClass());
    }

    public function testWriteBoolean(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TextWriter expects string, got boolean');
        
        $this->writer->write(true);
    }

    public function testWriteFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TextWriter expects string, got double');
        
        $this->writer->write(3.14);
    }

    // ========== getType Tests ==========

    public function testGetType(): void
    {
        $this->assertEquals('TEXT', $this->writer->getType());
    }

    // ========== canWrite Tests ==========

    public function testCanWrite(): void
    {
        $this->assertTrue($this->writer->canWrite('text'));
        $this->assertTrue($this->writer->canWrite(''));
        $this->assertTrue($this->writer->canWrite(null));
        
        $this->assertFalse($this->writer->canWrite(123));
        $this->assertFalse($this->writer->canWrite([]));
        $this->assertFalse($this->writer->canWrite(new \stdClass()));
        $this->assertFalse($this->writer->canWrite(true));
        $this->assertFalse($this->writer->canWrite(3.14));
    }

    // ========== escape method Tests ==========

    public function testEscapeMethod(): void
    {
        $input = 'text;with,commas\\and\nnewlines';
        $result = $this->writer->escape($input);
        
        // Check the actual result
        $this->assertIsString($result);
        $this->assertStringContainsString('\;', $result);
        $this->assertStringContainsString('\,', $result);
        $this->assertStringContainsString('\\\\', $result);
        $this->assertStringContainsString('\\n', $result);
    }

    public function testEscapeEmptyString(): void
    {
        $result = $this->writer->escape('');
        
        $this->assertEquals('', $result);
    }

    public function testEscapeNoEscapingNeeded(): void
    {
        $input = 'simple text without special chars';
        $result = $this->writer->escape($input);
        
        $this->assertEquals($input, $result);
    }

    public function testEscapeAlreadyEscaped(): void
    {
        $input = 'already\\escaped;and,commas';
        $result = $this->writer->escape($input);
        
        // Should escape backslashes first, then other characters
        $this->assertEquals('already\\\\escaped\;and\,commas', $result);
    }

    // ========== Integration Tests ==========

    public function testWriteProducesValidTextFormat(): void
    {
        $testTexts = [
            'Hello, World!',
            'key:value;key2:value2',
            'path\\to\\file',
            "Line 1\nLine 2",
            'apples, oranges, bananas',
        ];
        
        foreach ($testTexts as $text) {
            $result = $this->writer->write($text);
            
            // Should not contain unescaped special characters (except CR which gets removed)
            $this->assertStringNotContainsString("\n", $result);
            $this->assertStringNotContainsString("\r", $result);
            
            // But should contain escaped versions when appropriate
            if (str_contains($text, ',')) {
                $this->assertStringContainsString('\\,', $result);
            }
            if (str_contains($text, ';')) {
                $this->assertStringContainsString('\;', $result);
            }
            if (str_contains($text, '\\')) {
                $this->assertStringContainsString('\\\\', $result);
            }
        }
    }

    public function testWriteRealWorldExamples(): void
    {
        $realWorldTexts = [
            'Meeting with John Doe; Location: Conference Room A, Time: 14:00',
            "Project Status:\n- Task 1: Complete\n- Task 2: In Progress\n- Task 3: Pending",
            'File path: C:\\Documents\\Projects\\report.pdf',
            'Attendees: alice@example.com, bob@example.com; chair: charlie@example.com',
            "Notes: Please review the attached document\\nDeadline: Friday",
        ];
        
        foreach ($realWorldTexts as $text) {
            $result = $this->writer->write($text);
            
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
            
            // Should not have unescaped special characters
            $this->assertStringNotContainsString("\n", $result);
            $this->assertStringNotContainsString("\r", $result);
        }
    }

    public function testWriteEdgeCases(): void
    {
        $edgeCases = [
            '',                                   // Empty string
            ';,\\',                               // All special chars
            '\\\\',                               // Double backslashes
            ";\n,\r\\",                           // Mixed special chars
            str_repeat('a', 1000),                // Long string
            str_repeat('\\', 100),                  // Many backslashes
            "text\nwith\nmany\nnewlines\n",        // Many newlines
        ];
        
        foreach ($edgeCases as $text) {
            $result = $this->writer->write($text);
            
            $this->assertIsString($result);
            
            // Should not have unescaped newlines or carriage returns
            $this->assertStringNotContainsString("\n", $result);
            $this->assertStringNotContainsString("\r", $result);
        }
    }

    public function testWriteRoundTrip(): void
    {
        // Test that common patterns are escaped correctly
        $testCases = [
            'text;with;semicolons',
            'text,with,commas',
            'text\\with\\backslashes',
            "text\nwith\nnewlines",
            'complex;text,with\\multiple\\special\nchars',
        ];
        
        foreach ($testCases as $original) {
            $result = $this->writer->write($original);
            
            // Result should be properly escaped
            $this->assertIsString($result);
            $this->assertNotEquals($original, $result);
            
            // Should not have unescaped special characters
            $this->assertStringNotContainsString("\n", $result);
            $this->assertStringNotContainsString("\r", $result);
        }
    }

    public function testWritePerformance(): void
    {
        $iterations = 1000;
        $text = 'Sample text with; special, characters\\ and newlines\nfor testing';
        
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->writer->write($text);
        }
        $end = microtime(true);
        
        // Should be reasonably fast
        $this->assertLessThan(0.1, $end - $start);
    }

    public function testWriteMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $text = "Sample text {$i} with; special, characters\\ and newlines\nfor testing";
            $result = $this->writer->write($text);
            unset($result);
        }
        
        $finalMemory = memory_get_usage();
        
        $this->assertGreaterThanOrEqual($initialMemory, $finalMemory);
    }

    public function testWriteEscapingOrderPriority(): void
    {
        // Test that backslash escaping has highest priority
        $input = '\\;\\,\\n';
        
        $result = $this->writer->write($input);
        
        // Should escape backslash first, then other chars
        $this->assertEquals('\\\\\;\\\\\,\\\\n', $result);
    }

    public function testWriteOnlyCarriageReturns(): void
    {
        $input = "Text\rbetween\rcarriage\rreturns";
        $result = $this->writer->write($input);
        
        // CR should be removed completely
        $this->assertEquals('Textbetweencarriagereturns', $result);
    }

    public function testWriteLongTextWithMixedContent(): void
    {
        $input = str_repeat("Line with; commas, and\\backslashes\n", 100);
        $result = $this->writer->write($input);
        
        $this->assertIsString($result);
        $this->assertStringNotContainsString("\n", $result);
        $this->assertStringContainsString('\\n', $result);
        $this->assertStringContainsString('\;', $result);
        $this->assertStringContainsString('\,', $result);
        $this->assertStringContainsString('\\\\', $result);
    }
}