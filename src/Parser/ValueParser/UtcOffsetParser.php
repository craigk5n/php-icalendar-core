<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for UTC-OFFSET values according to RFC 5545 §3.3.14
 *
 * UTC-OFFSET format is [+/-]HHMM[SS] where:
 * - HH is hours offset (-23 to +23)
 * - MM is minutes offset (00-59)
 * - SS is optional seconds offset (00-59)
 */
class UtcOffsetParser implements ValueParserInterface
{
    public const ERR_INVALID_UTC_OFFSET = 'ICAL-TYPE-014';

    public function parse(string $value, array $parameters = []): \DateInterval
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty UTC-OFFSET value',
                self::ERR_INVALID_UTC_OFFSET
            );
        }

        if (!preg_match('/^([+-])(\d{2})(\d{2})(?:(\d{2}))?$/', $value, $matches)) {
            throw new ParseException(
                'Invalid UTC-OFFSET format: ' . $value . ' (expected [+/-]HHMM or [+/-]HHMMSS)',
                self::ERR_INVALID_UTC_OFFSET
            );
        }

        $sign = $matches[1];
        $hours = (int) $matches[2];
        $minutes = (int) $matches[3];
        $seconds = isset($matches[4]) ? (int) $matches[4] : 0;

        if ($hours > 23) {
            throw new ParseException(
                'Invalid UTC-OFFSET: hours must be 00-23, got: ' . $hours,
                self::ERR_INVALID_UTC_OFFSET
            );
        }

        if ($minutes > 59) {
            throw new ParseException(
                'Invalid UTC-OFFSET: minutes must be 00-59, got: ' . $minutes,
                self::ERR_INVALID_UTC_OFFSET
            );
        }

        if ($seconds > 59) {
            throw new ParseException(
                'Invalid UTC-OFFSET: seconds must be 00-59, got: ' . $seconds,
                self::ERR_INVALID_UTC_OFFSET
            );
        }

        $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

        if ($sign === '-') {
            $totalSeconds = -$totalSeconds;
        }

        $interval = new \DateInterval('PT0S');
        $interval->invert = $totalSeconds < 0 ? 1 : 0;
        $interval->days = 0;
        $interval->h = abs((int) ($totalSeconds / 3600));
        $interval->i = abs((int) (($totalSeconds % 3600) / 60));
        $interval->s = abs($totalSeconds % 60);

        return $interval;
    }

    public function getType(): string
    {
        return 'UTC-OFFSET';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/^[+-]\d{4}(?:\d{2})?$/', $value);
    }
}
