<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for URI values according to RFC 5545 §3.3.15
 *
 * URI is a universal resource identifier (RFC 3986).
 */
class UriParser implements ValueParserInterface
{
    public const ERR_INVALID_URI = 'ICAL-TYPE-013';

    public function parse(string $value, array $parameters = []): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty URI value',
                self::ERR_INVALID_URI
            );
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new ParseException(
                'Invalid URI format: ' . $value,
                self::ERR_INVALID_URI
            );
        }

        return $value;
    }

    public function getType(): string
    {
        return 'URI';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }
}
