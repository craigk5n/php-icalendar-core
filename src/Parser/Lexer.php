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
class Lexer
{
    private int $lineNumber = 0;

    /**
     * Tokenize iCalendar data into content lines
     * 
     * @return \Generator<ContentLine>
     * @throws ParseException When malformed content is encountered
     */
    public function tokenize(string $data): \Generator
    {
        $this->lineNumber = 0;
        
        // Normalize line endings to CRLF
        $normalized = $this->normalizeLineEndings($data);
        
        // Split by CRLF and unfold continuation lines
        $lines = $this->unfoldLines($normalized);
        
        foreach ($lines as $unfoldedLine) {
            if ($unfoldedLine === '') {
                // Skip empty lines (common between components)
                continue;
            }

            if (strpos($unfoldedLine, ':') === false) {
                $lineNumber = $this->lineNumber + 1;
                throw new ParseException(
                    "Malformed content line: missing ':' separator",
                    ParseException::ERR_INVALID_PROPERTY_FORMAT,
                    $lineNumber,
                    $unfoldedLine
                );
            }

            yield ContentLine::parse($unfoldedLine, ++$this->lineNumber);
        }
    }

    /**
     * Tokenize from file with streaming (constant memory)
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
        $buffer = '';
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
                $chunk = fread($handle, 8192); // Read in 8KB chunks
                if ($chunk === false) {
                    break;
                }

                $buffer .= $chunk;
                
                // Process complete lines from buffer
                $lines = $this->processBuffer($buffer);
                
                foreach ($lines as $line) {
                    if ($line === '') {
                        continue;
                    }

                    if (strpos($line, ':') === false) {
                        $lineNumber = $this->lineNumber + 1;
                        throw new ParseException(
                            "Malformed content line: missing ':' separator",
                            ParseException::ERR_INVALID_PROPERTY_FORMAT,
                            $lineNumber,
                            $line
                        );
                    }

                    yield ContentLine::parse($line, ++$this->lineNumber);
                }
            }
        } finally {
            fclose($handle);
        }

        // Process any remaining content in buffer
        if ($buffer !== '') {
            $remainingLines = $this->splitLines($buffer);
            foreach ($remainingLines as $line) {
                if ($line === '') {
                    continue;
                }

                if (strpos($line, ':') === false) {
                    $lineNumber = $this->lineNumber + 1;
                    throw new ParseException(
                        "Malformed content line: missing ':' separator",
                        ParseException::ERR_INVALID_PROPERTY_FORMAT,
                        $lineNumber,
                        $line
                    );
                }

                yield ContentLine::parse($line, ++$this->lineNumber);
            }
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
     * According to RFC 5545 §3.1:
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
        
        foreach ($lines as $index => $line) {
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
     * @param string $buffer Buffer containing partial lines
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
