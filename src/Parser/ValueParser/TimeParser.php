<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for TIME values according to RFC 5545 §3.3.12
 *
 * TIME format is HHMMSS[Z] where:
 * - HH is hours (00-23)
 * - MM is minutes (00-59)
 * - SS is seconds (00-60, allows for leap seconds)
 * - Z suffix indicates UTC time
 */
class TimeParser implements ValueParserInterface
{
    public const ERR_INVALID_TIME = 'ICAL-TYPE-012';

    public function parse(string $value, array $parameters = []): \DateTimeImmutable
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty TIME value',
                self::ERR_INVALID_TIME
            );
        }

        $isUtc = str_ends_with($value, 'Z');

        if ($isUtc) {
            $timePart = substr($value, 0, -1);
        } else {
            $timePart = $value;
        }

        if (!preg_match('/^(\d{2})(\d{2})(\d{2})$/', $timePart, $matches)) {
            throw new ParseException(
                'Invalid TIME format: ' . $value . ' (expected HHMMSS or HHMMSSZ)',
                self::ERR_INVALID_TIME
            );
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];
        $seconds = (int) $matches[3];

        if ($hours > 23) {
            throw new ParseException(
                'Invalid TIME: hours must be 00-23, got: ' . $hours,
                self::ERR_INVALID_TIME
            );
        }

        if ($minutes > 59) {
            throw new ParseException(
                'Invalid TIME: minutes must be 00-59, got: ' . $minutes,
                self::ERR_INVALID_TIME
            );
        }

        if ($seconds > 60) {
            throw new ParseException(
                'Invalid TIME: seconds must be 00-60, got: ' . $seconds,
                self::ERR_INVALID_TIME
            );
        }

        $timezone = $isUtc ? new \DateTimeZone('UTC') : new \DateTimeZone('+0000');

        return new \DateTimeImmutable(
            sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
            $timezone
        );
    }

    public function getType(): string
    {
        return 'TIME';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        $isUtc = str_ends_with($value, 'Z');

        if ($isUtc) {
            $timePart = substr($value, 0, -1);
        } else {
            $timePart = $value;
        }

        return (bool) preg_match('/^\d{6}$/', $timePart);
    }
}
