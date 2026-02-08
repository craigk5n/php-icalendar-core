<?php

declare(strict_types=1);

namespace Icalendar\Tests\Parser;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\LineFolder;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LineFolder class
 *
 * Per RFC 5545 §3.1:
 * - Folding: Insert CRLF followed by a single space or tab
 * - Unfolding: Remove CRLF followed by a single space or tab
 */
class LineFolderTest extends TestCase
{
    private LineFolder $folder;

    #[\Override]
    protected function setUp(): void
    {
        $this->folder = new LineFolder();
    }

    public function testUnfoldSimpleLine(): void
    {
        $input = "SUMMARY:Team Meeting\r\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:Team Meeting", $result);
    }

    public function testUnfoldMultipleLines(): void
    {
        // Per RFC 5545, the space/tab after CRLF is removed during unfolding
        $input = "SUMMARY:This is a very long summary that needs\r\n to be folded\r\n across multiple lines\r\n";
        $result = $this->folder->unfold($input);

        // Note: The leading space on continuation lines is removed
        $this->assertEquals("SUMMARY:This is a very long summary that needsto be foldedacross multiple lines", $result);
    }

    public function testUnfoldWithTab(): void
    {
        $input = "DESCRIPTION:First part\r\n\tsecond part\r\n";
        $result = $this->folder->unfold($input);

        // Tab is removed during unfolding
        $this->assertEquals("DESCRIPTION:First partsecond part", $result);
    }

    public function testUnfoldPreservesWhitespace(): void
    {
        // Internal whitespace in values should be preserved
        $input = "SUMMARY:  Leading spaces preserved\r\n and more text\r\n";
        $result = $this->folder->unfold($input);

        // Original leading spaces preserved, but continuation space removed
        $this->assertEquals("SUMMARY:  Leading spaces preservedand more text", $result);
    }

    public function testUnfoldDetectsMalformed(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Malformed folding: continuation line without preceding content');

        // Line starts with space but has no preceding content line
        $input = " SUMMARY:Team Meeting\r\n";
        $this->folder->unfold($input);
    }

    public function testUnfoldUtf8Sequences(): void
    {
        // UTF-8 characters should be preserved
        $input = "SUMMARY:日本語\r\n テキスト\r\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:日本語テキスト", $result);
    }

    public function testUnfoldWithLfOnly(): void
    {
        // Should handle LF-only line endings
        $input = "SUMMARY:Line one\n Line two\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:Line oneLine two", $result);
    }

    public function testUnfoldWithCrOnly(): void
    {
        // Should handle CR-only line endings (normalized to CRLF)
        $input = "SUMMARY:Line one\r Line two\r";
        $result = $this->folder->unfold($input);

        // After normalization and unfolding, continuation removed
        $this->assertEquals("SUMMARY:Line oneLine two", $result);
    }

    public function testUnfoldMixedLineEndings(): void
    {
        // Should handle mixed line endings
        // Note: spaces after line breaks are continuation markers and are removed
        $input = "SUMMARY:Line one\r\n Line two\n Line three\r Line four\r\n";
        $result = $this->folder->unfold($input);

        // All spaces after line breaks are continuation markers, so removed
        $this->assertEquals("SUMMARY:Line oneLine twoLine threeLine four", $result);
    }

    public function testUnfoldConsecutiveFoldedLines(): void
    {
        // Multiple consecutive folded lines
        $input = "SUMMARY:Line one\r\n two\r\n three\r\n four\r\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:Line onetwothreefour", $result);
    }

    public function testUnfoldMultipleContentLines(): void
    {
        // Multiple content lines, some folded
        $input = "SUMMARY:First line\r\n continued\r\nDTSTART:20260210\r\nDTEND:20260211\r\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:First linecontinued\r\nDTSTART:20260210\r\nDTEND:20260211", $result);
    }

    public function testUnfoldPreservesInternalWhitespace(): void
    {
        // Internal whitespace should be preserved exactly
        $input = "SUMMARY:Hello   World\r\n  More   Spaces\r\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:Hello   World More   Spaces", $result);
    }

    public function testUnfoldEmptyInput(): void
    {
        $result = $this->folder->unfold('');
        $this->assertEquals('', $result);
    }

    public function testUnfoldEmptyLines(): void
    {
        // Empty lines between content
        $input = "SUMMARY:Line one\r\n\r\nDTSTART:20260210\r\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:Line one\r\n\r\nDTSTART:20260210", $result);
    }

    public function testFoldLongLine(): void
    {
        // Create a line longer than 75 octets
        $longValue = str_repeat('A', 100);
        $input = "SUMMARY:{$longValue}";

        $result = $this->folder->fold($input);

        // Result should contain line breaks
        $this->assertStringContainsString("\r\n", $result);

        // Each line should be 75 octets or less
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(75, $this->folder->getOctetLength($line), "Line exceeds 75 octets: {$line}");
        }
    }

    public function testFoldUtf8Sequence(): void
    {
        // UTF-8 multi-byte characters
        $input = "SUMMARY:" . str_repeat('日本語', 20);  // 9 bytes per repetition

        $result = $this->folder->fold($input);

        // Check that no UTF-8 sequence is split
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            // Try to validate UTF-8
            $this->assertTrue(
                mb_check_encoding($line, 'UTF-8'),
                "UTF-8 sequence was split in line: {$line}"
            );
        }
    }

    public function testFoldAtLogicalBoundary(): void
    {
        // Should fold properly and be reversible
        $input = "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;CN=John Doe:mailto:john@example.com";

        $result = $this->folder->fold($input);

        // Result should be valid (no lines over 75 octets)
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(75, $this->folder->getOctetLength($line));
        }

        // Unfolding should restore original
        $unfolded = $this->folder->unfold($result);
        $this->assertEquals($input, $unfolded);
    }

    public function testFoldNoBoundary(): void
    {
        // Line with no logical boundaries (just continuous text)
        $input = "SUMMARY:" . str_repeat('A', 100);

        $result = $this->folder->fold($input);

        // Should still fold correctly
        $lines = explode("\r\n", $result);
        $this->assertGreaterThan(1, count($lines));

        // Unfold should restore original
        $unfolded = $this->folder->unfold($result);
        $this->assertEquals($input, $unfolded);
    }

    public function testFoldExact75Octets(): void
    {
        // Line that is exactly 75 octets should not be folded
        $input = "SUMMARY:" . str_repeat('A', 67);  // 8 + 67 = 75

        $result = $this->folder->fold($input);

        $this->assertEquals($input, $result);
    }

    public function testFold76Octets(): void
    {
        // Line that is 76 octets should be folded
        $input = "SUMMARY:" . str_repeat('A', 68);  // 8 + 68 = 76

        $result = $this->folder->fold($input);

        $this->assertStringContainsString("\r\n", $result);
    }

    public function testRoundTrip(): void
    {
        // Fold then unfold should restore original
        $input = "SUMMARY:This is a very long description that contains multiple words and should be folded properly when we process it through the line folder class\r\nDTSTART:20260210T100000";

        $folded = $this->folder->fold($input);
        $unfolded = $this->folder->unfold($folded);

        $this->assertEquals($input, $unfolded);
    }

    public function testGetOctetLength(): void
    {
        // ASCII characters
        $this->assertEquals(5, $this->folder->getOctetLength('Hello'));

        // UTF-8 multi-byte characters (3 bytes each)
        $this->assertEquals(9, $this->folder->getOctetLength('日本語'));

        // Mixed
        $this->assertEquals(14, $this->folder->getOctetLength('Hello日本語'));
    }

    public function testUnfoldWithContinuationSpace(): void
    {
        // Per RFC 5545, the space after CRLF is removed
        $input = "SUMMARY:First\r\n Second\r\n";
        $result = $this->folder->unfold($input);

        // Space is consumed by unfolding mechanism
        $this->assertEquals("SUMMARY:FirstSecond", $result);
    }

    public function testUnfoldWithContinuationTab(): void
    {
        // Tab continuation
        $input = "SUMMARY:First\r\n\tSecond\r\n";
        $result = $this->folder->unfold($input);

        $this->assertEquals("SUMMARY:FirstSecond", $result);
    }

    public function testFoldMultipleLines(): void
    {
        $input = "SUMMARY:Short line\r\nDESCRIPTION:" . str_repeat('B', 100) . "\r\nDTSTART:20260210";

        $result = $this->folder->fold($input);

        // Each line in result should be <= 75 octets
        $lines = explode("\r\n", $result);
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(75, $this->folder->getOctetLength($line));
        }

        // Unfold should restore
        $unfolded = $this->folder->unfold($result);
        $this->assertEquals($input, $unfolded);
    }

    public function testFoldPreservesContentAfter75(): void
    {
        // Line with content after 75 octet boundary
        $input = "SUMMARY:1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890END";

        $result = $this->folder->fold($input);

        // Unfold should restore original
        $unfolded = $this->folder->unfold($result);
        $this->assertEquals($input, $unfolded);
    }
}
