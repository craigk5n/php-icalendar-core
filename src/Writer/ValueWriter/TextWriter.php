<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

use Icalendar\Validation\SecurityValidator;

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
    private SecurityValidator $securityValidator;

    public function __construct(?SecurityValidator $securityValidator = null)
    {
        $this->securityValidator = $securityValidator ?? new SecurityValidator();
    }

    #[\Override]
    public function write(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('TextWriter expects string, got ' . gettype($value));
        }

        // The TEXT ABNF excludes CONTROL characters, so strip them before
        // escaping. Order matters: sanitizeText() emits a backslash ('\x01') and
        // escape() must be what doubles it. Reversed, the output would carry a
        // bare '\x', which is not a defined escape and reparses lossily.
        return $this->escape($this->securityValidator->sanitizeText($value));
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