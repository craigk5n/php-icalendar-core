<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Exception\ParseException;

/**
 * Handles line folding and unfolding for iCalendar content
 *
 * RFC 5545 §3.1 specifies that content lines can be "folded" by inserting CRLF
 * followed by a single space (U+0020) or tab (U+0009) character.
 */
class LineFolder
{
    /**
     * Unfold content lines by removing folding sequences (CRLF + space/tab)
     *
     * @param string $data The raw iCalendar data
     * @return string The unfolded data
     * @throws ParseException if malformed folding is detected
     */
    public function unfold(string $data): string
    {
        // Normalize line endings to CRLF first
        $data = $this->normalizeLineEndings($data);

        // Remove trailing CRLF if present
        $data = rtrim($data, "\r\n");

            // Split into lines by CRLF
        $lines = explode("\r\n", $data);
        $result = [];
        $currentLine = '';

        // Track if we have processed any lines
        $hasProcessedLines = false;

        foreach ($lines as $index => $line) {
            // Check if this line starts with space or tab (continuation)
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t")) {
                // This is a continuation line
                // Continuation without a preceding line - malformed
                if (!$hasProcessedLines) {
                    throw new ParseException(
                        'Malformed folding: continuation line without preceding content',
                        ParseException::ERR_MALFORMED_FOLDING,
                        $index + 1,
                        $line
                    );
                }

                // Remove the leading space/tab and append to current line
                $currentLine .= substr($line, 1);
            } else {
                // This is a new line (or empty line)
                if ($hasProcessedLines) {
                    $result[] = $currentLine;
                }
                $currentLine = $line;
                $hasProcessedLines = true;
            }
        }

        // Don't forget the last line
        $result[] = $currentLine;

        // Reassemble with CRLF
        return implode("\r\n", $result);
    }

    /**
     * Normalize various line ending formats to CRLF
     */
    private function normalizeLineEndings(string $data): string
    {
        // Convert all line endings to CRLF
        // Step 1: Replace CRLF with LF (temporarily)
        $data = str_replace("\r\n", "\n", $data);
        // Step 2: Replace remaining CR with LF
        $data = str_replace("\r", "\n", $data);
        // Step 3: Replace all LF with CRLF
        $data = str_replace("\n", "\r\n", $data);

        return $data;
    }

    /**
     * Fold content lines at 75 octets or less
     *
     * @param string $data The unfolded data
     * @param int $maxLength Maximum line length in octets (default 75)
     * @return string The folded data
     */
    public function fold(string $data, int $maxLength = 75): string
    {
        $lines = explode("\r\n", $data);
        $result = [];

        foreach ($lines as $line) {
            if ($line === '') {
                $result[] = '';
                continue;
            }

            // Calculate octet length
            $octetLength = mb_strlen($line, '8bit');

            if ($octetLength <= $maxLength) {
                // Line is short enough, no folding needed
                $result[] = $line;
                continue;
            }

            // Fold the line
            $folded = $this->foldLine($line, $maxLength);
            $result[] = $folded;
        }

        return implode("\r\n", $result);
    }

    /**
     * Fold a single line at appropriate boundaries
     */
    private function foldLine(string $line, int $maxLength): string
    {
        $result = [];
        $current = '';
        $currentLength = 0;

        // Split line into characters (respecting UTF-8)
        $chars = mb_str_split($line);

        foreach ($chars as $char) {
            $charLength = mb_strlen($char, '8bit');

            // Check if adding this character would exceed the limit
            if ($currentLength + $charLength > $maxLength && $current !== '') {
                // Start a new folded line
                $result[] = $current;
                $current = ' ' . $char;  // Add space for continuation
                $currentLength = 1 + $charLength;  // 1 for space, plus char length
            } else {
                $current .= $char;
                $currentLength += $charLength;
            }
        }

        // Don't forget the last part
        if ($current !== '') {
            $result[] = $current;
        }

        return implode("\r\n", $result);
    }

    /**
     * Get the octet length of a string
     */
    public function getOctetLength(string $str): int
    {
        return mb_strlen($str, '8bit');
    }
}