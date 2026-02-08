<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for TEXT values with proper escaping
 *
 * RFC 5545 §3.3.11: Escape sequences
 * - \\ → \
 * - \; → ;
 * - \, → ,
 * - \n or \N → newline
 */
class TextWriter implements ValueWriterInterface
{
    #[\Override]
    public function write(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('TextWriter expects string, got ' . gettype($value));
        }

        return $this->escape($value);
    }

    /**
     * Escape text according to RFC 5545
     */
    public function escape(string $text): string
    {
        // Order matters: escape backslash first, then other characters
        // Process CRLF before bare CR/LF to avoid double-escaping
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace("\r\n", '\\n', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);

        return $text;
    }

    #[\Override]
    public function getType(): string
    {
        return 'TEXT';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_string($value) || is_null($value);
    }
}