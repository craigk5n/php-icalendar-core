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
        $this->assertIsString($tempFile);
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
        $this->assertIsString($tempFile);
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
        if (function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->markTestSkipped('Root bypasses filesystem permissions, so an unreadable file cannot be simulated.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
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

    public function testTokenizeFileWithFoldedLines(): void
    {
        // Folded ATTENDEE line that would fail without unfolding in tokenizeFile
        $data = "BEGIN:VCALENDAR\r\n"
            . "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=\"Baloo Knudsen\"\r\n"
            . " :mailto:baloo@example.com\r\n"
            . "END:VCALENDAR\r\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $data);

        $lines = iterator_to_array($this->lexer->tokenizeFile($tempFile));
        unlink($tempFile);

        $this->assertCount(3, $lines);
        // The ATTENDEE line should be unfolded with the continuation
        $attendeeLine = $lines[1];
        $this->assertEquals('ATTENDEE', $attendeeLine->getName());
        $this->assertStringContainsString('mailto:baloo@example.com', $attendeeLine->getValue());
    }

    public function testTokenizeFileWithTabFoldedLines(): void
    {
        $data = "BEGIN:VCALENDAR\r\n"
            . "DESCRIPTION:This is a long description\r\n"
            . "\tthat continues here\r\n"
            . "END:VCALENDAR\r\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $data);

        $lines = iterator_to_array($this->lexer->tokenizeFile($tempFile));
        unlink($tempFile);

        $this->assertCount(3, $lines);
        $this->assertEquals('DESCRIPTION', $lines[1]->getName());
        $this->assertEquals('This is a long descriptionthat continues here', $lines[1]->getValue());
    }

    public function testTokenizeFileWithMultipleFoldedLines(): void
    {
        // Line folded across multiple continuations
        $data = "BEGIN:VCALENDAR\r\n"
            . "DESCRIPTION:Line one\r\n"
            . " line two\r\n"
            . " line three\r\n"
            . "VERSION:2.0\r\n"
            . "END:VCALENDAR\r\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $data);

        $lines = iterator_to_array($this->lexer->tokenizeFile($tempFile));
        unlink($tempFile);

        $this->assertCount(4, $lines);
        $this->assertEquals('DESCRIPTION', $lines[1]->getName());
        $this->assertEquals('Line oneline twoline three', $lines[1]->getValue());
        $this->assertEquals('VERSION', $lines[2]->getName());
    }

    public function testTokenizeLenientSkipsMalformedLines(): void
    {
        $this->lexer->setStrict(false);

        $data = "BEGIN:VCALENDAR\r\nMALFORMED_NO_COLON\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n";
        $lines = iterator_to_array($this->lexer->tokenize($data));

        // Malformed line should be skipped
        $this->assertCount(3, $lines);
        $this->assertEquals('BEGIN', $lines[0]->getName());
        $this->assertEquals('VERSION', $lines[1]->getName());
        $this->assertEquals('END', $lines[2]->getName());

        // Warning should be collected
        $warnings = $this->lexer->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString("missing ':' separator", $warnings[0]['message']);
        $this->assertEquals('MALFORMED_NO_COLON', $warnings[0]['line']);
    }

    public function testTokenizeFileLenientSkipsMalformedLines(): void
    {
        $this->lexer->setStrict(false);

        $data = "BEGIN:VCALENDAR\r\nMALFORMED_NO_COLON\r\nVERSION:2.0\r\nEND:VCALENDAR\r\n";
        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $data);

        $lines = iterator_to_array($this->lexer->tokenizeFile($tempFile));
        unlink($tempFile);

        $this->assertCount(3, $lines);
        $this->assertEquals('BEGIN', $lines[0]->getName());
        $this->assertEquals('VERSION', $lines[1]->getName());
        $this->assertEquals('END', $lines[2]->getName());

        $warnings = $this->lexer->getWarnings();
        $this->assertCount(1, $warnings);
    }

    public function testTokenizeStrictStillThrowsOnMalformedLine(): void
    {
        // Default strict mode should still throw
        $this->expectException(ParseException::class);
        iterator_to_array($this->lexer->tokenize("MALFORMED_LINE\r\nVERSION:2.0\r\n"));
    }

    public function testTokenizeLenientHandlesAttendeeWithoutValue(): void
    {
        $this->lexer->setStrict(false);

        // Real-world malformed ATTENDEE line missing :mailto: value
        $data = "BEGIN:VEVENT\r\n"
            . "SUMMARY:Team Meeting\r\n"
            . "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=\"Baloo Knudsen\"\r\n"
            . "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;CN=\"Craig Knudsen\":mailto:craig@k5n.us\r\n"
            . "END:VEVENT\r\n";

        $lines = iterator_to_array($this->lexer->tokenize($data));

        // Malformed ATTENDEE (no colon) should be skipped, valid one kept
        $this->assertCount(4, $lines);
        $this->assertEquals('BEGIN', $lines[0]->getName());
        $this->assertEquals('SUMMARY', $lines[1]->getName());
        $this->assertEquals('ATTENDEE', $lines[2]->getName());
        $this->assertStringContainsString('mailto:craig@k5n.us', $lines[2]->getValue());
        $this->assertEquals('END', $lines[3]->getName());

        // Warning should be recorded for the skipped line
        $warnings = $this->lexer->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Baloo Knudsen', $warnings[0]['line']);
    }

    public function testTokenizeFileLenientHandlesAttendeeWithoutValue(): void
    {
        $this->lexer->setStrict(false);

        $data = "BEGIN:VEVENT\r\n"
            . "SUMMARY:Team Meeting\r\n"
            . "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=\"Baloo Knudsen\"\r\n"
            . "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;CN=\"Craig Knudsen\":mailto:craig@k5n.us\r\n"
            . "END:VEVENT\r\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $data);

        $lines = iterator_to_array($this->lexer->tokenizeFile($tempFile));
        unlink($tempFile);

        $this->assertCount(4, $lines);
        $this->assertEquals('ATTENDEE', $lines[2]->getName());
        $this->assertStringContainsString('mailto:craig@k5n.us', $lines[2]->getValue());

        $warnings = $this->lexer->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Baloo Knudsen', $warnings[0]['line']);
    }

    public function testTokenizeStrictThrowsOnAttendeeWithoutValue(): void
    {
        // In strict mode, the same malformed ATTENDEE should throw
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("missing ':' separator");

        $data = "BEGIN:VEVENT\r\n"
            . "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=\"Baloo Knudsen\"\r\n"
            . "END:VEVENT\r\n";

        iterator_to_array($this->lexer->tokenize($data));
    }

    // -------------------------------------------------------
    // Double quotes in property values
    // -------------------------------------------------------

    public function testTokenizeDescriptionWithDoubleQuote(): void
    {
        $data = "BEGIN:VCALENDAR\r\nDESCRIPTION:We got 16\" of snow\r\nEND:VCALENDAR\r\n";

        $lines = iterator_to_array($this->lexer->tokenize($data));

        $this->assertCount(3, $lines);
        $this->assertEquals('DESCRIPTION', $lines[1]->getName());
        $this->assertEquals('We got 16" of snow', $lines[1]->getValue());
    }

    public function testTokenizeSummaryWithBalancedQuotes(): void
    {
        $data = "SUMMARY:Watch \"The Matrix\" tonight\r\n";

        $lines = iterator_to_array($this->lexer->tokenize($data));

        $this->assertCount(1, $lines);
        $this->assertEquals('Watch "The Matrix" tonight', $lines[0]->getValue());
    }

    public function testTokenizeDescriptionWithOddQuotes(): void
    {
        // Single (odd) quote in the value must not throw
        $data = "DESCRIPTION:The board is 3/4\" thick\r\n";

        $lines = iterator_to_array($this->lexer->tokenize($data));

        $this->assertCount(1, $lines);
        $this->assertEquals('The board is 3/4" thick', $lines[0]->getValue());
    }

    public function testTokenizeFileDescriptionWithDoubleQuote(): void
    {
        $data = "BEGIN:VCALENDAR\r\n"
            . "BEGIN:VEVENT\r\n"
            . "SUMMARY:Snow Day\r\n"
            . "DESCRIPTION:We got 16\" of snow\r\n"
            . "END:VEVENT\r\n"
            . "END:VCALENDAR\r\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $data);

        $lines = iterator_to_array($this->lexer->tokenizeFile($tempFile));
        unlink($tempFile);

        $this->assertCount(6, $lines);
        $descLine = $lines[3];
        $this->assertEquals('DESCRIPTION', $descLine->getName());
        $this->assertEquals('We got 16" of snow', $descLine->getValue());
    }

    public function testTokenizeLenientContinuesAfterQuoteInValue(): void
    {
        $this->lexer->setStrict(false);

        // Multiple events - one with a quote in the value
        $data = "BEGIN:VCALENDAR\r\n"
            . "BEGIN:VEVENT\r\n"
            . "SUMMARY:Event with 16\" measurement\r\n"
            . "END:VEVENT\r\n"
            . "BEGIN:VEVENT\r\n"
            . "SUMMARY:Normal Event\r\n"
            . "END:VEVENT\r\n"
            . "END:VCALENDAR\r\n";

        $lines = iterator_to_array($this->lexer->tokenize($data));

        // All lines should be parsed - the quote in the value is legal
        $this->assertCount(8, $lines);
    }

    public function testTokenizeLenientSkipsPropertyParseErrors(): void
    {
        $this->lexer->setStrict(false);

        // Invalid property name (starts with digit) - PropertyParser will throw
        $data = "BEGIN:VCALENDAR\r\n"
            . "123INVALID:some value\r\n"
            . "VERSION:2.0\r\n"
            . "END:VCALENDAR\r\n";

        $lines = iterator_to_array($this->lexer->tokenize($data));

        // The invalid property should be skipped, others remain
        $this->assertCount(3, $lines);
        $this->assertEquals('BEGIN', $lines[0]->getName());
        $this->assertEquals('VERSION', $lines[1]->getName());
        $this->assertEquals('END', $lines[2]->getName());

        // Warning should be collected
        $warnings = $this->lexer->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Invalid property name', $warnings[0]['message']);
    }

    public function testTokenizeFileLenientSkipsPropertyParseErrors(): void
    {
        $this->lexer->setStrict(false);

        $data = "BEGIN:VCALENDAR\r\n"
            . "123INVALID:some value\r\n"
            . "VERSION:2.0\r\n"
            . "END:VCALENDAR\r\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'ical_test_');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, $data);

        $lines = iterator_to_array($this->lexer->tokenizeFile($tempFile));
        unlink($tempFile);

        $this->assertCount(3, $lines);
        $this->assertEquals('BEGIN', $lines[0]->getName());
        $this->assertEquals('VERSION', $lines[1]->getName());
        $this->assertEquals('END', $lines[2]->getName());

        $warnings = $this->lexer->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Invalid property name', $warnings[0]['message']);
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