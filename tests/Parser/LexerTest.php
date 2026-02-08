<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Parser\Lexer;
use Icalendar\Parser\ContentLine;
use Icalendar\Exception\ParseException;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
    private Lexer $lexer;

    #[\Override]
    protected function setUp(): void
    {
        $this->lexer = new Lexer();
    }

    public function testTokenizeSimpleCalendar(): void
    {
        $data = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n";
        
        $lines = iterator_to_array($this->lexer->tokenize($data));
        
        $this->assertGreaterThanOrEqual(2, count($lines));
        
        $firstLine = $lines[0] ?? null;
        if ($firstLine) {
            $this->assertStringContainsString('BEGIN:VCALENDAR', $firstLine->getRawLine());
            $this->assertEquals('BEGIN', $firstLine->getName());
            $this->assertEquals('VCALENDAR', $firstLine->getValue());
        }
    }

    public function testTokenizeWithFoldedLines(): void
    {
        // Test basic folding functionality
        $longText = str_repeat('A', 76);
        $line1 = 'DESCRIPTION:' . $longText;
        $continuation = ' that continues on the next line.';
        
        $data = $line1 . "\r\n " . $continuation . "\r\nVERSION:2.0\r\n";
        
        $lines = iterator_to_array($this->lexer->tokenize($data));
        
        $this->assertCount(2, $lines);
        
        // First line should be unfolded
        $expectedFirst = 'DESCRIPTION:' . $longText . ' that continues on the next line.';
        $this->assertEquals($expectedFirst, $lines[0]->getRawLine());
        $this->assertEquals($longText . ' that continues on the next line.', $lines[0]->getValue());
        
        // Second line should be VERSION
        $this->assertEquals('VERSION:2.0', $lines[1]->getRawLine());
    }

    public function testTokenizeNormalizesLineEndings(): void
    {
        $data = "BEGIN:VCALENDAR\nVERSION:2.0\r\nEND:VCALENDAR";
        
        $lines = iterator_to_array($this->lexer->tokenize($data));
        
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $lines[0]->getRawLine());
        $this->assertStringContainsString('VERSION:2.0', $lines[1]->getRawLine());
        $this->assertStringContainsString('END:VCALENDAR', $lines[2]->getRawLine());
    }

    public function testTokenizeTracksLineNumbers(): void
    {
        $data = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n";
        
        $lines = iterator_to_array($this->lexer->tokenize($data));
        
        $this->assertEquals(1, $lines[0]->getContentLineNumber());
        $this->assertEquals(2, $lines[1]->getContentLineNumber());
        $this->assertEquals(3, $lines[2]->getContentLineNumber());
    }

    public function testTokenizeFileStreaming(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        file_put_contents($tempFile, "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n");
        
        $lineCount = 0;
        foreach ($this->lexer->tokenizeFile($tempFile) as $line) {
            $lineCount++;
            $this->assertInstanceOf(ContentLine::class, $line);
        }
        
        $this->assertEquals(3, $lineCount);
        
        unlink($tempFile);
    }

    public function testTokenizeLargeFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ical_large_');
        $content = '';
        
        // Create a large amount of data to test memory efficiency
        for ($i = 1; $i <= 1000; $i++) {
            $content .= "PROP{$i}:value{$i}\r\n";
        }
        file_put_contents($tempFile, $content);
        
        $memoryBefore = memory_get_peak_usage(true);
        $lineCount = 0;
        
        foreach ($this->lexer->tokenizeFile($tempFile) as $line) {
            $lineCount++;
            $this->assertInstanceOf(ContentLine::class, $line);
            $name = $line->getName();
            $value = $line->getValue();
            $this->assertNotEmpty($name);
            $this->assertNotEmpty($value);
        }
        
        $memoryAfter = memory_get_peak_usage(true);
        $memoryIncrease = $memoryAfter - $memoryBefore;
        
        // Should use reasonable memory (less than 50MB for 10K lines)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
        $this->assertEquals(1000, $lineCount);
        
        unlink($tempFile);
    }

    public function testTokenizeDetectsMalformedLine(): void
    {
        $this->expectException(ParseException::class);

        // Must iterate the generator to trigger the exception
        iterator_to_array($this->lexer->tokenize("MALFORMED_LINE_WITHOUT_COLON\r\nVERSION:2.0\r\n"));
    }

    public function testTokenizeWithTabContinuation(): void
    {
        // Test folding with tab continuation - first line must be a valid property
        $longValue = str_repeat('A', 60);
        $data = "DESCRIPTION:" . $longValue . "\r\n\tthat continues with a tab\r\nVERSION:2.0\r\n";

        $lines = iterator_to_array($this->lexer->tokenize($data));

        $this->assertCount(2, $lines);

        // First line should be unfolded
        $expectedFirst = "DESCRIPTION:" . $longValue . 'that continues with a tab';
        $this->assertEquals($expectedFirst, $lines[0]->getRawLine());
        $this->assertEquals($longValue . 'that continues with a tab', $lines[0]->getValue());

        // Second line should be VERSION
        $this->assertEquals('VERSION:2.0', $lines[1]->getRawLine());
    }

    public function testTokenizeSkipsEmptyLines(): void
    {
        $data = "BEGIN:VCALENDAR\r\n\r\nVERSION:2.0\r\n\r\nEND:VCALENDAR\r\n";
        
        $lines = iterator_to_array($this->lexer->tokenize($data));
        
        $this->assertCount(3, $lines);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $lines[0]->getRawLine());
        $this->assertStringContainsString('VERSION:2.0', $lines[1]->getRawLine());
        $this->assertStringContainsString('END:VCALENDAR', $lines[2]->getRawLine());
    }

    public function testTokenizeFileNotFound(): void
    {
        try {
            // tokenizeFile throws before returning generator for file-not-found
            iterator_to_array($this->lexer->tokenizeFile('/nonexistent/file.ics'));
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            $this->assertEquals(ParseException::ERR_FILE_NOT_FOUND, $e->getErrorCode());
        }
    }

    public function testTokenizeFileNotReadable(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        file_put_contents($tempFile, "BEGIN:VCALENDAR\r\nEND:VCALENDAR");
        chmod($tempFile, 0000); // Make unreadable

        try {
            iterator_to_array($this->lexer->tokenizeFile($tempFile));
            $this->fail('Expected ParseException was not thrown');
        } catch (ParseException $e) {
            $this->assertEquals(ParseException::ERR_PERMISSION_DENIED, $e->getErrorCode());
        }
    }

    public function testUnfoldMalformedFolding(): void
    {
        // A line without a colon following a valid line should throw ParseException
        $this->expectException(ParseException::class);

        // "malformed_continuation_no_space" has no colon, so lexer throws
        $data = "PROP:value\r\nmalformed_continuation_no_space\r\n";

        iterator_to_array($this->lexer->tokenize($data));
    }

    public function testTokenizeHandlesUnicode(): void
    {
        $data = "SUMMARY:测试事件 with 🗓️\r\nDESCRIPTION:Multilingual content\r\n";
        
        $lines = iterator_to_array($this->lexer->tokenize($data));
        
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('测试事件 with 🗓️', $lines[0]->getRawLine());
        $this->assertStringContainsString('测试事件 with 🗓️', $lines[0]->getValue());
        $this->assertStringContainsString('DESCRIPTION:Multilingual content', $lines[1]->getRawLine());
    }

    public function testTokenizeMemoryEfficiency(): void
    {
        // Create a large amount of data to test memory efficiency
        $largeData = str_repeat("PROP:value\r\n", 10000);
        
        $memoryBefore = memory_get_peak_usage(true);
        $lineCount = 0;
        
        foreach ($this->lexer->tokenize($largeData) as $line) {
            $lineCount++;
            $this->assertInstanceOf(ContentLine::class, $line);
            $name = $line->getName();
            $value = $line->getValue();
            $this->assertNotEmpty($name);
            $this->assertNotEmpty($value);
        }
        
        $memoryAfter = memory_get_peak_usage(true);
        $memoryIncrease = $memoryAfter - $memoryBefore;
        
        // Should use reasonable memory (less than 50MB for 10K lines)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
        $this->assertEquals(10000, $lineCount);
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Cleanup any temp files that might have been left behind
        foreach (glob(sys_get_temp_dir() . '/ical_test_*') as $file) {
            if (file_exists($file)) {
                chmod($file, 0644);
                unlink($file);
            }
        }
        foreach (glob(sys_get_temp_dir() . '/ical_large_*') as $file) {
            if (file_exists($file)) {
                chmod($file, 0644);
                unlink($file);
            }
        }
    }
}