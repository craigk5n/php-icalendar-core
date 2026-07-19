<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;
use Icalendar\Value\TextListValue;

/**
 * Parser for REQUEST-STATUS values according to RFC 5545 §3.8.8.3.
 *
 *     rstatus  = statcode ";" statdesc [";" extdata]
 *     statcode = 1*DIGIT *("." 1*DIGIT)
 *     statdesc = text
 *     extdata  = text
 *
 * A structured value whose semicolons are separators, not content. Without a
 * parser of its own it fell through to the TEXT default, which validated
 * nothing and — worse — let the TEXT writer escape those separators, emitting
 * `2.0\;Success`. That is the GEO defect of #18 in a second property.
 *
 * Splitting respects escaping, so a `\;` inside the description stays part of
 * that component instead of being read as a further separator. The trimmed
 * value is returned unchanged, preserving the caller's text for a byte-stable
 * round trip.
 */
class RequestStatusParser implements ValueParserInterface
{
    /** statcode: digits, optionally dot-separated. */
    private const STATCODE_PATTERN = '/^\d+(\.\d+)*$/';

    #[\Override]
    public function setStrict(bool $strict): void
    {
        // The grammar admits no leniency: a value either has a numeric status
        // code and a description, or it is not a REQUEST-STATUS.
    }

    /**
     * @param array<string, string> $parameters
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new ParseException(
                'Empty REQUEST-STATUS value',
                ParseException::ERR_INVALID_REQUEST_STATUS
            );
        }

        // Split on unescaped semicolons only: an escaped one is description text.
        $parts = TextListValue::splitOnUnescapedSemicolons($trimmed);

        if (count($parts) < 2 || count($parts) > 3) {
            throw new ParseException(
                'Invalid REQUEST-STATUS value: expected \'statcode;statdesc[;extdata]\', got: ' . $value,
                ParseException::ERR_INVALID_REQUEST_STATUS
            );
        }

        if (preg_match(self::STATCODE_PATTERN, $parts[0]) !== 1) {
            throw new ParseException(
                "Invalid REQUEST-STATUS status code: '{$parts[0]}' is not dot-separated digits",
                ParseException::ERR_INVALID_REQUEST_STATUS
            );
        }

        if (trim($parts[1]) === '') {
            throw new ParseException(
                'Invalid REQUEST-STATUS value: the status description is empty',
                ParseException::ERR_INVALID_REQUEST_STATUS
            );
        }

        return $trimmed;
    }

    #[\Override]
    public function getType(): string
    {
        return 'REQUEST-STATUS';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        try {
            $this->parse($value);
            return true;
        } catch (ParseException) {
            return false;
        }
    }
}
