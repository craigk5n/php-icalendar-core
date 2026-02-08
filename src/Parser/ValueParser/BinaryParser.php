<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for BINARY values according to RFC 5545 §3.3.1
 *
 * BINARY values are Base64-encoded data with line wrapping.
 * Lines are limited to 75 octets and may contain CRLF followed by a space/tab.
 */
class BinaryParser implements ValueParserInterface
{
    private bool $strict = false;

    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_BINARY = 'ICAL-TYPE-001';

    #[\Override]
    public function parse(string $value, array $parameters = []): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty BINARY value',
                self::ERR_INVALID_BINARY
            );
        }

        $unwrapped = $this->unwrapBase64($value);

        $decoded = base64_decode($unwrapped, true);

        if ($decoded === false) {
            throw new ParseException(
                'Invalid Base64 encoding in BINARY value',
                self::ERR_INVALID_BINARY
            );
        }

        return $decoded;
    }

    private function unwrapBase64(string $value): string
    {
        $lines = preg_split('/\r?\n/', $value);
        if ($lines === false) {
            return $value;
        }
        $unwrapped = '';

        foreach ($lines as $i => $line) {
            if ($i > 0) {
                $line = ltrim($line, " \t");
            }
            $unwrapped .= $line;
        }

        return $unwrapped;
    }

    #[\Override]
    public function getType(): string
    {
        return 'BINARY';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        $unwrapped = $this->unwrapBase64($value);

        $length = strlen($unwrapped);

        if ($length === 0) {
            return false;
        }

        if ($this->strict && $length % 4 !== 0) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $unwrapped);
    }
}