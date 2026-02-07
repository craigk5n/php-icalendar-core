<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use DateTimeImmutable;
use DateTimeZone;
use Icalendar\Exception\ParseException;

/**
 * Parser for DATE-TIME values according to RFC 5545
 */
class DateTimeParser implements ValueParserInterface
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public function parse(string $value, array $parameters = []): DateTimeImmutable
    {
        $isStandardFormat = $this->isStandardFormat($value);

        if (!$isStandardFormat) {
            if (!$this->strict) {
                try {
                    return new DateTimeImmutable($value);
                } catch (\Exception $e) {}
            }
            throw new ParseException("Invalid DATE-TIME format: '{$value}'. Expected YYYYMMDDTHHMMSS[Z].", ParseException::ERR_INVALID_DATE_TIME);
        }

        $isUtc = str_ends_with($value, 'Z');
        if (isset($parameters['TZID']) && !$isUtc) {
            return $this->parseWithTimezone($value, $parameters['TZID']);
        }

        return $isUtc ? $this->parseUtc($value) : $this->parseLocal($value);
    }

    public function getType(): string
    {
        return 'DATE-TIME';
    }

    public function canParse(string $value): bool
    {
        if ($this->isStandardFormat($value)) return true;
        if (!$this->strict) {
            try {
                new DateTimeImmutable($value);
                return true;
            } catch (\Exception $e) { return false; }
        }
        return false;
    }

    private function isStandardFormat(string $value): bool
    {
        $isUtc = str_ends_with($value, 'Z');
        $dateTimePart = $isUtc ? substr($value, 0, -1) : $value;
        
        if (strlen($dateTimePart) !== 15 || !preg_match('/^\d{8}T\d{6}$/', $dateTimePart)) {
            return false;
        }

        $year = (int) substr($dateTimePart, 0, 4);
        $month = (int) substr($dateTimePart, 4, 2);
        $day = (int) substr($dateTimePart, 6, 2);
        $hour = (int) substr($dateTimePart, 9, 2);
        $minute = (int) substr($dateTimePart, 11, 2);
        $second = (int) substr($dateTimePart, 13, 2);

        return DateValidator::isValidDate($year, $month, $day) &&
               $hour >= 0 && $hour <= 23 &&
               $minute >= 0 && $minute <= 59 &&
               $second >= 0 && $second <= 60;
    }

    private function parseUtc(string $value): DateTimeImmutable
    {
        $dateTimePart = substr($value, 0, -1);
        $year = (int) substr($dateTimePart, 0, 4);
        $month = (int) substr($dateTimePart, 4, 2);
        $day = (int) substr($dateTimePart, 6, 2);
        $hour = (int) substr($dateTimePart, 9, 2);
        $minute = (int) substr($dateTimePart, 11, 2);
        $second = (int) substr($dateTimePart, 13, 2);

        $dateTimeString = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
        return new DateTimeImmutable($dateTimeString, new DateTimeZone('UTC'));
    }

    private function parseWithTimezone(string $value, string $tzid): DateTimeImmutable
    {
        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);
        $hour = (int) substr($value, 9, 2);
        $minute = (int) substr($value, 11, 2);
        $second = (int) substr($value, 13, 2);

        $dateTimeString = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
        try {
            $timezone = new DateTimeZone($tzid);
        } catch (\Exception $e) {
            throw new ParseException("Invalid timezone: '{$tzid}'", ParseException::ERR_INVALID_DATE_TIME);
        }
        return new DateTimeImmutable($dateTimeString, $timezone);
    }

    private function parseLocal(string $value): DateTimeImmutable
    {
        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);
        $hour = (int) substr($value, 9, 2);
        $minute = (int) substr($value, 11, 2);
        $second = (int) substr($value, 13, 2);

        $dateTimeString = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
        return new DateTimeImmutable($dateTimeString);
    }
}
