<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for INTEGER values according to RFC 5545 §3.3.8
 */
class IntegerParser implements ValueParserInterface
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_INTEGER = 'ICAL-TYPE-008';

    public function parse(string $value, array $parameters = []): int
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException('Empty INTEGER value', self::ERR_INVALID_INTEGER);
        }

        if (!preg_match('/^[+-]?\d+$/', $value)) {
            // In lenient mode, we could try to handle common issues like float strings "42.0"
            if (!$this->strict && is_numeric($value)) {
                return (int) round((float) $value);
            }

            throw new ParseException('Invalid INTEGER format: ' . $value, self::ERR_INVALID_INTEGER);
        }

        return (int) $value;
    }

    public function getType(): string
    {
        return 'INTEGER';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);
        if ($value === '') return false;
        
        if (preg_match('/^[+-]?\d+$/', $value)) return true;
        
        if (!$this->strict && is_numeric($value)) return true;
        
        return false;
    }
}