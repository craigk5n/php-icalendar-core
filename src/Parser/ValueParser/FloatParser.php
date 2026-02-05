<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for FLOAT values according to RFC 5545 §3.3.7
 *
 * FLOAT is a decimal number. May be specified in signed or unsigned form.
 */
class FloatParser implements ValueParserInterface
{
    public const ERR_INVALID_FLOAT = 'ICAL-TYPE-007';

    public function parse(string $value, array $parameters = []): float
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty FLOAT value',
                self::ERR_INVALID_FLOAT
            );
        }

        if (!preg_match('/^-?\d+\.?\d*$/', $value)) {
            throw new ParseException(
                'Invalid FLOAT format: ' . $value,
                self::ERR_INVALID_FLOAT
            );
        }

        return (float) $value;
    }

    public function getType(): string
    {
        return 'FLOAT';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/^-?\d+\.?\d*$/', $value);
    }
}
