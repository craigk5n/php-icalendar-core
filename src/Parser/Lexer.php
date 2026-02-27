<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Exception\ParseException;

/**
 * Tokenizes raw iCalendar data into content lines
 *
 * The Lexer handles:
 * 1. Line ending normalization - Convert LF, CR, CRLF to CRLF
 * 2. Line unfolding - Join continuation lines (CRLF + space/tab)
 * 3. Token generation - Yield ContentLine objects via generator
 */
final class Lexer
{
    private int $lineNumber = 0;

    private bool $strict = true;

    /** @var array<array{message: string, line: string, lineNumber: int}> */
    private array $warnings = [];

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    /**
     * Get warnings collected during lenient tokenization.
     *
     * @return array<array{message: string, line: string, lineNumber: int}>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Tokenize iCalendar data into content lines
     *
     * @return \Generator<ContentLine>
     * @throws ParseException When malformed content is encountered (strict mode)
     */
    public function tokenize(string $data): \Generator
    {
        $this->lineNumber = 0;
        $this->warnings = [];

        // Normalize line endings to CRLF
        $normalized = $this->normalizeLineEndings($data);

        // Split by CRLF and unfold continuation lines
        $lines = $this->unfoldLines($normalized);

        foreach ($lines as $unfoldedLine) {
            if ($unfoldedLine === '') {
                continue;
            }

            if (strpos($unfoldedLine, ':') === false) {
                $lineNumber = $this->lineNumber + 1;
                if ($this->strict) {
                    throw new ParseException(
                        "Malformed content line: missing ':' separator",
                        ParseException::ERR_INVALID_PROPERTY_FORMAT,
                        $lineNumber,
                        $unfoldedLine
                    );
                }
                $this->warnings[] = [
                    'message' => "Malformed content line: missing ':' separator",
                    'line' => $unfoldedLine,
                    'lineNumber' => $lineNumber,
                ];
                $this->lineNumber++;
                continue;
            }

            $this->lineNumber++;
            try {
                yield ContentLine::parse($unfoldedLine, $this->lineNumber);
            } catch (ParseException $e) {
                if ($this->strict) {
                    throw $e;
                }
                $this->warnings[] = [
                    'message' => $e->getMessage(),
                    'line' => $unfoldedLine,
                    'lineNumber' => $this->lineNumber,
                ];
            }
        }
    }

    /**
     * Tokenize from file with streaming (constant memory)
     *
     * Handles line unfolding across chunk boundaries by maintaining a
     * pending line that is only emitted once we confirm the next line
     * is not a continuation.
     *
     * @return \Generator<ContentLine>
     * @throws ParseException When file cannot be read or contains malformed content
     * @throws \RuntimeException When file operations fail
     */
    public function tokenizeFile(string $filepath): \Generator
    {
        if (!file_exists($filepath)) {
            throw new ParseException(
                "File not found: {$filepath}",
                ParseException::ERR_FILE_NOT_FOUND,
                0
            );
        }

        if (!is_readable($filepath)) {
            throw new ParseException(
                "File not readable: {$filepath}",
                ParseException::ERR_PERMISSION_DENIED,
                0
            );
        }

        $this->lineNumber = 0;
        $this->warnings = [];
        $buffer = '';
        $pendingLine = null;
        $handle = fopen($filepath, 'r');

        if ($handle === false) {
            throw new ParseException(
                "Failed to open file: {$filepath}",
                ParseException::ERR_FILE_NOT_FOUND,
                0
            );
        }

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    break;
                }

                $buffer .= $chunk;
                $rawLines = $this->processBuffer($buffer);

                foreach ($rawLines as $rawLine) {
                    // Check if this is a continuation line (starts with space or tab)
                    if ($rawLine !== '' && ($rawLine[0] === ' ' || $rawLine[0] === "\t")) {
                        if ($pendingLine !== null) {
                            $pendingLine .= substr($rawLine, 1);
                        } else {
                            // Orphan continuation - treat content as-is
                            $pendingLine = substr($rawLine, 1);
                        }
                        continue;
                    }

                    // Not a continuation - emit the pending line
                    if ($pendingLine !== null) {
                        $result = $this->processContentLine($pendingLine);
                        if ($result !== null) {
                            yield $result;
                        }
                    }
                    $pendingLine = $rawLine;
                }
            }
        } finally {
            fclose($handle);
        }

        // Process any remaining content in buffer (incomplete line without trailing CRLF)
        if ($buffer !== '') {
            $remainingLines = $this->splitLines($buffer);
            foreach ($remainingLines as $rawLine) {
                if ($rawLine !== '' && ($rawLine[0] === ' ' || $rawLine[0] === "\t")) {
                    if ($pendingLine !== null) {
                        $pendingLine .= substr($rawLine, 1);
                    } else {
                        $pendingLine = substr($rawLine, 1);
                    }
                    continue;
                }

                if ($pendingLine !== null) {
                    $result = $this->processContentLine($pendingLine);
                    if ($result !== null) {
                        yield $result;
                    }
                }
                $pendingLine = $rawLine;
            }
        }

        // Flush the final pending line
        if ($pendingLine !== null) {
            $result = $this->processContentLine($pendingLine);
            if ($result !== null) {
                yield $result;
            }
        }
    }

    /**
     * Validate and parse a single content line, respecting strict/lenient mode.
     *
     * @return ContentLine|null The parsed content line, or null if skipped
     * @throws ParseException In strict mode when the line is malformed
     */
    private function processContentLine(string $line): ?ContentLine
    {
        if ($line === '') {
            return null;
        }

        if (strpos($line, ':') === false) {
            $lineNumber = $this->lineNumber + 1;
            if ($this->strict) {
                throw new ParseException(
                    "Malformed content line: missing ':' separator",
                    ParseException::ERR_INVALID_PROPERTY_FORMAT,
                    $lineNumber,
                    $line
                );
            }
            $this->warnings[] = [
                'message' => "Malformed content line: missing ':' separator",
                'line' => $line,
                'lineNumber' => $lineNumber,
            ];
            $this->lineNumber++;
            return null;
        }

        $this->lineNumber++;
        try {
            return ContentLine::parse($line, $this->lineNumber);
        } catch (ParseException $e) {
            if ($this->strict) {
                throw $e;
            }
            $this->warnings[] = [
                'message' => $e->getMessage(),
                'line' => $line,
                'lineNumber' => $this->lineNumber,
            ];
            return null;
        }
    }

    /**
     * Normalize line endings to CRLF
     *
     * @param string $data Input data with any line endings
     * @return string Data with normalized CRLF endings
     */
    private function normalizeLineEndings(string $data): string
    {
        // Convert all line endings to LF first, then to CRLF
        $data = str_replace(["\r\n", "\r"], "\n", $data);
        return str_replace("\n", "\r\n", $data);
    }

    /**
     * Unfold continuation lines
     *
     * According to RFC 5545 Section 3.1:
     * Lines of text SHOULD NOT be longer than 75 octets
     * Long content lines SHOULD be folded using a folding technique
     *
     * The folding technique: break a line at any point where there is a
     * whitespace (space or horizontal tab) character, and insert CRLF immediately
     * before the whitespace character.
     *
     * NOTE: Folding is OPTIONAL in practice. Lines can be longer than 75 octets.
     * We should not enforce folding but support it when it exists.
     *
     * @param string $data Data with CRLF line endings
     * @return array<string> Array of unfolded lines
     */
    public function unfoldLines(string $data): array
    {
        $lines = explode("\r\n", $data);
        $unfolded = [];
        $currentLine = '';

        foreach ($lines as $line) {
            // Check if this line is a continuation (starts with space or tab)
            if (strlen($line) > 0 && ($line[0] === ' ' || $line[0] === "\t")) {
                // Valid continuation line
                $currentLine .= substr($line, 1);
                continue;
            }

            // Not a continuation, add current accumulated line if any
            if ($currentLine !== '') {
                $unfolded[] = $currentLine;
            }

            $currentLine = $line;
        }

        // Add the last line
        if ($currentLine !== '') {
            $unfolded[] = $currentLine;
        }

        return $unfolded;
    }

    /**
     * Process buffer to extract complete lines
     *
     * @param string $buffer Buffer containing partial lines (modified by reference)
     * @return array<string> Array of complete lines
     */
    private function processBuffer(string &$buffer): array
    {
        $lines = [];
        $lastPos = 0;

        // Find complete lines ending with CRLF
        while (($pos = strpos($buffer, "\r\n", $lastPos)) !== false) {
            $line = substr($buffer, $lastPos, $pos - $lastPos);
            $lines[] = $line;
            $lastPos = $pos + 2; // Skip CRLF
        }

        // Keep incomplete part in buffer
        if ($lastPos > 0) {
            $buffer = substr($buffer, $lastPos);
        } else {
            $buffer = '';
        }

        return $lines;
    }

    /**
     * Split buffer into lines by CRLF (for final processing)
     *
     * @param string $buffer Remaining buffer
     * @return array<string> Array of lines
     */
    private function splitLines(string $buffer): array
    {
        return explode("\r\n", $buffer);
    }
}
