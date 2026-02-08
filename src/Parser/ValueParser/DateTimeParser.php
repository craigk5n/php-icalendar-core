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

    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    /**
     * @param array<string, string> $parameters
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): DateTimeImmutable
    {
        $formatCheck = $this->checkFormat($value);

        if ($formatCheck === 'invalid_format') {
            if (!$this->strict) {
                try {
                    return new DateTimeImmutable($value);
                } catch (\Exception $e) {}
            }
            throw new ParseException("Invalid DATE-TIME format: '{$value}'. Expected YYYYMMDDTHHMMSS[Z].", ParseException::ERR_INVALID_DATE_TIME);
        }

        if ($formatCheck === 'invalid_value') {
            throw new ParseException("Invalid DATE-TIME value: '{$value}'.", ParseException::ERR_INVALID_DATE_TIME);
        }

        $isUtc = str_ends_with($value, 'Z');
        if (isset($parameters['TZID']) && !$isUtc) {
            return $this->parseWithTimezone($value, $parameters['TZID']);
        }

        return $isUtc ? $this->parseUtc($value) : $this->parseLocal($value);
    }

    #[\Override]
    public function getType(): string
    {
        return 'DATE-TIME';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        if ($this->checkFormat($value) === 'valid') return true;
        if (!$this->strict) {
            try {
                new DateTimeImmutable($value);
                return true;
            } catch (\Exception $e) { return false; }
        }
        return false;
    }

    /**
     * Check if value has valid DATE-TIME format and values.
     * Returns 'valid', 'invalid_format', or 'invalid_value'.
     */
    private function checkFormat(string $value): string
    {
        $isUtc = str_ends_with($value, 'Z');
        $dateTimePart = $isUtc ? substr($value, 0, -1) : $value;

        if (strlen($dateTimePart) !== 15 || !preg_match('/^\d{8}T\d{6}$/', $dateTimePart)) {
            return 'invalid_format';
        }

        $year = (int) substr($dateTimePart, 0, 4);
        $month = (int) substr($dateTimePart, 4, 2);
        $day = (int) substr($dateTimePart, 6, 2);
        $hour = (int) substr($dateTimePart, 9, 2);
        $minute = (int) substr($dateTimePart, 11, 2);
        $second = (int) substr($dateTimePart, 13, 2);

        if (!DateValidator::isValidDate($year, $month, $day) ||
            $hour > 23 || $minute > 59 || $second > 60) {
            return 'invalid_value';
        }

        return 'valid';
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
            if ($tzid === '') {
                throw new \Exception('Empty TZID');
            }
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
