<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for BOOLEAN values according to RFC 5545 §3.3.2
 *
 * BOOLEAN values are case-insensitive TRUE or FALSE.
 */
class BooleanParser implements ValueParserInterface
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_BOOLEAN = 'ICAL-TYPE-002';

    public function parse(string $value, array $parameters = []): bool
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty BOOLEAN value',
                self::ERR_INVALID_BOOLEAN
            );
        }

        $lowerValue = strtolower($value);

        if ($lowerValue === 'true') {
            return true;
        }

        if ($lowerValue === 'false') {
            return false;
        }

        if ($this->strict) {
            throw new ParseException(
                'Invalid BOOLEAN value: ' . $value . ' (must be TRUE or FALSE)',
                self::ERR_INVALID_BOOLEAN
            );
        }

        return false;
    }

    public function getType(): string
    {
        return 'BOOLEAN';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);
        $lowerValue = strtolower($value);

        return $lowerValue === 'true' || $lowerValue === 'false';
    }
}