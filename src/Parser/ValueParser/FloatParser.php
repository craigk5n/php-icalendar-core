<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for FLOAT values according to RFC 5545 §3.3.7
 */
class FloatParser implements ValueParserInterface
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_FLOAT = 'ICAL-TYPE-007';

    public function parse(string $value, array $parameters = []): float
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException('Empty FLOAT value', self::ERR_INVALID_FLOAT);
        }

        if (!preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
            // In lenient mode, we could try to handle common issues like ".5" instead of "0.5"
            if (!$this->strict && preg_match('/^[+-]?\d*\.\d+$/', $value)) {
                return (float) $value;
            }

            throw new ParseException('Invalid FLOAT format: ' . $value, self::ERR_INVALID_FLOAT);
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
        if ($value === '') return false;
        
        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) return true;
        
        if (!$this->strict && preg_match('/^[+-]?\d*\.\d+$/', $value)) return true;
        
        return false;
    }
}