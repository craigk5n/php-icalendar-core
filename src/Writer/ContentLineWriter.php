<?php

declare(strict_types=1);

namespace Icalendar\Writer;

/**
 * Handles line folding for iCalendar output
 *
 * According to RFC 5545 §3.1:
 * - Lines longer than 75 octets should be folded
 * - Never fold within UTF-8 multi-byte sequences
 * - CRLF followed by a single space or tab is the fold delimiter
 * - Prefer folding at logical boundaries when possible
 */
class ContentLineWriter
{
    private int $maxLength;
    private bool $foldingEnabled;

    public function __construct(int $maxLength = 75, bool $foldingEnabled = true)
    {
        $this->maxLength = $maxLength;
        $this->foldingEnabled = $foldingEnabled;
    }

    /**
     * Write content with proper line folding
     *
     * @param string $content The content to write
     * @return string The content with proper line endings and folding
     */
    public function write(string $content): string
    {
        // Normalize line endings to CRLF
        $content = $this->normalizeLineEndings($content);

        // Remove trailing CRLF
        $content = rtrim($content, "\r\n");

        if (!$this->foldingEnabled) {
            return $content;
        }

        // Split into lines and fold each one
        $lines = explode("\r\n", $content);
        $result = [];

        foreach ($lines as $line) {
            if ($line === '') {
                $result[] = '';
                continue;
            }

            $octetLength = $this->getOctetLength($line);

            if ($octetLength <= $this->maxLength) {
                $result[] = $line;
            } else {
                $result[] = $this->foldLine($line);
            }
        }

        return implode("\r\n", $result);
    }

    /**
     * Fold a single line at appropriate boundaries
     *
     * @param string $line The line to fold
     * @return string The folded line
     */
    private function foldLine(string $line): string
    {
        $result = [];
        $current = '';
        $currentLength = 0;

        // Logical boundary positions (prefer folding after these)
        $boundaryChars = [';', ',', ' ', '=', ':'];

        $chars = $this->mbStringToArray($line);
        $totalChars = count($chars);

        for ($i = 0; $i < $totalChars; $i++) {
            $char = $chars[$i];
            $charLength = $this->getOctetLength($char);

            // Check if adding this character would exceed the limit
            $wouldExceed = $currentLength + $charLength > $this->maxLength;

            // Check if we can fold at a logical boundary before this character
            $canFoldAtBoundary = false;
            if ($wouldExceed && $current !== '') {
                $canFoldAtBoundary = $this->canFoldAtBoundary($current, $boundaryChars);
            }

            if ($wouldExceed && $canFoldAtBoundary) {
                // Fold at the boundary
                $result[] = $current;
                $current = ' ' . $char;
                $currentLength = 1 + $charLength;
            } elseif ($wouldExceed && $currentLength > 0) {
                // Can't fold at boundary, fold in middle of content
                $result[] = $current;
                $current = ' ' . $char;
                $currentLength = 1 + $charLength;
            } else {
                $current .= $char;
                $currentLength += $charLength;
            }
        }

        if ($current !== '') {
            $result[] = $current;
        }

        return implode("\r\n", $result);
    }

    /**
     * Check if we can fold at a logical boundary
     *
     * @param string $current The current folded segment
     * @param string[] $boundaryChars Characters that are good fold boundaries
     * @return bool True if we can fold at a boundary
     */
    private function canFoldAtBoundary(string $current, array $boundaryChars): bool
    {
        $lastChar = substr($current, -1);

        // Don't fold right after a fold delimiter (space)
        if ($lastChar === ' ') {
            return false;
        }

        return in_array($lastChar, $boundaryChars, true);
    }

    /**
     * Get the octet length of a string
     *
     * @param string $str The string to measure
     * @return int The length in octets
     */
    public function getOctetLength(string $str): int
    {
        return mb_strlen($str, '8bit');
    }

    /**
     * Normalize line endings to CRLF
     *
     * @param string $data The data to normalize
     * @return string The normalized data
     */
    private function normalizeLineEndings(string $data): string
    {
        $data = str_replace("\r\n", "\n", $data);
        $data = str_replace("\r", "\n", $data);
        $data = str_replace("\n", "\r\n", $data);
        return $data;
    }

    /**
     * Convert a string to an array of characters (respecting UTF-8)
     *
     * @param string $str The string to convert
     * @return string[] Array of characters
     */
    private function mbStringToArray(string $str): array
    {
        $result = [];
        $length = mb_strlen($str, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $result[] = mb_substr($str, $i, 1, 'UTF-8');
        }

        return $result;
    }

    /**
     * Set the maximum line length
     *
     * @param int $length The maximum length in octets
     */
    public function setMaxLength(int $length): void
    {
        $this->maxLength = $length;
    }

    /**
     * Get the maximum line length
     *
     * @return int The maximum length in octets
     */
    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    /**
     * Enable or disable folding
     *
     * @param bool $enabled True to enable folding
     */
    public function setFoldingEnabled(bool $enabled): void
    {
        $this->foldingEnabled = $enabled;
    }

    /**
     * Check if folding is enabled
     *
     * @return bool True if folding is enabled
     */
    public function isFoldingEnabled(): bool
    {
        return $this->foldingEnabled;
    }
}
