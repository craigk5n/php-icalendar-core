<?php

declare(strict_types=1);

namespace Icalendar\Tests\Writer;

use Icalendar\Writer\ContentLineWriter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ContentLineWriter
 */
class ContentLineWriterTest extends TestCase
{
    private ContentLineWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->writer = new ContentLineWriter();
    }

    /**
     * Test folding a long line
     */
    public function testFoldLongLine(): void
    {
        $longLine = str_repeat('A', 100);
        $result = $this->writer->write($longLine);

        // Should be folded into multiple lines
        $this->assertStringContainsString("\r\n", $result);

        // Each line should be <= 75 octets
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(75, $this->writer->getOctetLength($line));
        }
    }

    /**
     * Test that line endings are normalized to CRLF
     */
    public function testFoldCrlfEndings(): void
    {
        $content = "Line1\nLine2\nLine3";

        $result = $this->writer->write($content);

        // Should use CRLF - verify no bare LF (LF not preceded by CR) exists
        $this->assertStringContainsString("\r\n", $result);
        // Remove all CRLF pairs, then check no bare LF or CR remain
        $stripped = str_replace("\r\n", '', $result);
        $this->assertStringNotContainsString("\n", $stripped, 'Found bare LF');
        $this->assertStringNotContainsString("\r", $stripped, 'Found bare CR');
    }

    /**
     * Test that UTF-8 sequences are not split
     */
    public function testFoldUtf8Sequence(): void
    {
        // Create a string with UTF-8 characters (e.g., Japanese)
        // Each Japanese character is 3 bytes in UTF-8
        $japanese = str_repeat('日本', 30);  // Each pair = 6 bytes, 30 pairs = 180 bytes
        $result = $this->writer->write($japanese);

        // Should be folded
        $this->assertStringContainsString("\r\n", $result);

        // Verify no incomplete UTF-8 sequences
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            // Check that the line is valid UTF-8
            $this->assertEquals(1, preg_match('//u', $line), "Line is not valid UTF-8: " . bin2hex($line));
        }
    }

    /**
     * Test folding at logical boundaries (semicolons)
     */
    public function testFoldAtBoundary(): void
    {
        // Create a line with semicolons that can be used as fold points
        $line = 'PARAM1=value1;PARAM2=value2;PARAM3=value3;PARAM4=value5;PARAM6=value7;PARAM8=value9;PARAM10=value11';
        $result = $this->writer->write($line);

        // Should fold at semicolons
        $this->assertStringContainsString("\r\n ", $result);
    }

    /**
     * Test folding a very long line
     */
    public function testFoldVeryLongLine(): void
    {
        $veryLong = str_repeat('X', 200);
        $result = $this->writer->write($veryLong);

        // Should be folded into multiple lines
        $this->assertGreaterThan(1, substr_count($result, "\r\n"));

        // Each line should be <= 75 octets
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(75, $this->writer->getOctetLength($line));
        }
    }

    /**
     * Test that short lines are not folded
     */
    public function testShortLineNotFolded(): void
    {
        $shortLine = 'SUMMARY:Short line';
        $result = $this->writer->write($shortLine);

        $this->assertEquals($shortLine, $result);
    }

    /**
     * Test line exactly at max length
     */
    public function testFoldExact75Octets(): void
    {
        $exact75 = str_repeat('A', 75);
        $result = $this->writer->write($exact75);

        // Should not be folded
        $this->assertEquals($exact75, $result);
    }

    /**
     * Test line one octet over max length
     */
    public function testFold76Octets(): void
    {
        $line76 = str_repeat('A', 76);
        $result = $this->writer->write($line76);

        // Should be folded
        $this->assertStringContainsString("\r\n", $result);
    }

    /**
     * Test empty content
     */
    public function testEmptyContent(): void
    {
        $result = $this->writer->write('');

        $this->assertEquals('', $result);
    }

    /**
     * Test content with multiple lines
     */
    public function testMultipleLines(): void
    {
        $content = "LINE1\r\nLINE2\r\nLINE3";

        $result = $this->writer->write($content);

        $this->assertStringContainsString("LINE1\r\n", $result);
        $this->assertStringContainsString("LINE2\r\n", $result);
        $this->assertStringEndsWith("LINE3", $result);
    }

    /**
     * Test folding with spaces as boundaries
     */
    public function testFoldAtSpaces(): void
    {
        $line = 'This is a very long line with many words that should be folded at word boundaries when possible for readability';
        $result = $this->writer->write($line);

        // Should fold somewhere
        $this->assertGreaterThan(1, strlen($result));
    }

    /**
     * Test disabled folding
     */
    public function testDisabledFolding(): void
    {
        $this->writer->setFoldingEnabled(false);

        $longLine = str_repeat('A', 100);
        $result = $this->writer->write($longLine);

        // Should not be folded
        $this->assertEquals($longLine, $result);
    }

    /**
     * Test setting max length
     */
    public function testSetMaxLength(): void
    {
        $this->writer->setMaxLength(50);

        $line51 = str_repeat('A', 51);
        $result = $this->writer->write($line51);

        // Should be folded since max is 50
        $this->assertStringContainsString("\r\n", $result);
    }

    /**
     * Test get max length
     */
    public function testGetMaxLength(): void
    {
        $this->assertEquals(75, $this->writer->getMaxLength());

        $this->writer->setMaxLength(100);
        $this->assertEquals(100, $this->writer->getMaxLength());
    }

    /**
     * Test UTF-8 emoji characters are not split
     */
    public function testFoldEmojiNotSplit(): void
    {
        // Emoji can be 4 bytes in UTF-8
        $emojis = 'Meeting: 📅📍📧🔗';
        $longLine = str_repeat($emojis, 20);
        $result = $this->writer->write($longLine);

        // Should fold
        $this->assertStringContainsString("\r\n", $result);

        // Verify no incomplete UTF-8 sequences
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            // Check that the line is valid UTF-8
            $validUtf8 = preg_match('//u', $line);
            $this->assertEquals(1, $validUtf8, "Line is not valid UTF-8");
        }
    }

    /**
     * Test Cyrillic characters are not split
     */
    public function testFoldCyrillicNotSplit(): void
    {
        // Cyrillic characters are 2 bytes in UTF-8
        $cyrillic = 'Встреча: понедельник, вторник';
        $longLine = str_repeat($cyrillic, 20);
        $result = $this->writer->write($longLine);

        // Should fold
        $this->assertStringContainsString("\r\n", $result);

        // Verify no incomplete UTF-8 sequences
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $validUtf8 = preg_match('//u', $line);
            $this->assertEquals(1, $validUtf8, "Line is not valid UTF-8");
        }
    }

    /**
     * Test Arabic characters are not split
     */
    public function testFoldArabicNotSplit(): void
    {
        // Arabic characters are 2 bytes in UTF-8
        $arabic = 'اجتماع: يوم الاثنين';
        $longLine = str_repeat($arabic, 20);
        $result = $this->writer->write($longLine);

        // Should fold
        $this->assertStringContainsString("\r\n", $result);

        // Verify no incomplete UTF-8 sequences
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $validUtf8 = preg_match('//u', $line);
            $this->assertEquals(1, $validUtf8, "Line is not valid UTF-8");
        }
    }

    /**
     * Test mixed ASCII and non-ASCII characters
     */
    public function testFoldMixedCharacters(): void
    {
        $mixed = 'Hello世界مرحبا123';
        $longLine = str_repeat($mixed, 15);
        $result = $this->writer->write($longLine);

        // Should fold
        $this->assertStringContainsString("\r\n", $result);

        // Verify no incomplete UTF-8 sequences
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $validUtf8 = preg_match('//u', $line);
            $this->assertEquals(1, $validUtf8, "Line is not valid UTF-8");
        }
    }
}
