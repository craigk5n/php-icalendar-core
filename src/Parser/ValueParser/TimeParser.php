<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for TIME values according to RFC 5545 §3.3.12
 */
class TimeParser implements ValueParserInterface
{
    private bool $strict = false;

    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_TIME = 'ICAL-TYPE-012';

    /**
     * @param array<string, string> $parameters
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): \DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') throw new ParseException('Empty TIME value', self::ERR_INVALID_TIME);

        $isUtc = str_ends_with($value, 'Z');
        $timePart = $isUtc ? substr($value, 0, -1) : $value;

        if (!preg_match('/^(\d{2})(\d{2})(\d{2})$/', $timePart, $matches)) {
            if (!$this->strict && preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $timePart, $matches)) {
                // matched
            } else {
                throw new ParseException('Invalid TIME format: ' . $value, self::ERR_INVALID_TIME);
            }
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];
        $seconds = (int) $matches[3];

        if ($hours > 23) throw new ParseException("Invalid TIME: hours must be 00-23, got: $hours", self::ERR_INVALID_TIME);
        if ($minutes > 59) throw new ParseException("Invalid TIME: minutes must be 00-59, got: $minutes", self::ERR_INVALID_TIME);
        if ($seconds > 60) throw new ParseException("Invalid TIME: seconds must be 00-60, got: $seconds", self::ERR_INVALID_TIME);

        $tzid = $parameters['TZID'] ?? '';
        $timezone = $isUtc ? new \DateTimeZone('UTC') : 
                    (($tzid !== '') ? new \DateTimeZone($tzid) : new \DateTimeZone(date_default_timezone_get()));

        return new \DateTimeImmutable(sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds), $timezone);
    }

    #[\Override]
    public function getType(): string
    {
        return 'TIME';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        $value = trim($value);
        if ($value === '') return false;
        $isUtc = str_ends_with($value, 'Z');
        $timePart = $isUtc ? substr($value, 0, -1) : $value;
        if (preg_match('/^\d{6}$/', $timePart)) return true;
        if (!$this->strict && preg_match('/^\d{2}:\d{2}:\d{2}$/', $timePart)) return true;
        return false;
    }
}
