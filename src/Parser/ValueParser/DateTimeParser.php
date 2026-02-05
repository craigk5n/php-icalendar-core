<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use DateTimeImmutable;
use DateTimeZone;
use Icalendar\Exception\ParseException;

/**
 * Parser for DATE-TIME values according to RFC 5545
 *
 * DATE-TIME formats:
 * - Local: YYYYMMDDTHHMMSS
 * - UTC: YYYYMMDDTHHMMSSZ
 * - TZID referenced: YYYYMMDDTHHMMSS (with TZID parameter)
 *
 * Examples:
 * - 20260205T100000 (local time)
 * - 20260205T100000Z (UTC time)
 * - 20260205T100000 (with TZID=America/New_York)
 */
class DateTimeParser implements ValueParserInterface
{
    /**
     * Parse a DATE-TIME value
     *
     * @param string $value The datetime string in YYYYMMDDTHHMMSS[Z] format
     * @param array<string, string> $parameters Property parameters (e.g., TZID)
     * @return DateTimeImmutable The parsed datetime
     * @throws ParseException if the datetime format is invalid
     */
    public function parse(string $value, array $parameters = []): DateTimeImmutable
    {
        if (!$this->canParse($value)) {
            throw new ParseException(
                "Invalid DATE-TIME format: '{$value}'. Expected YYYYMMDDTHHMMSS[Z].",
                ParseException::ERR_INVALID_DATE_TIME
            );
        }

        // Check for UTC suffix
        $isUtc = str_ends_with($value, 'Z');

        // Parse based on format
        if ($isUtc) {
            return $this->parseUtc($value);
        }

        // Check for TZID parameter
        if (isset($parameters['TZID'])) {
            return $this->parseWithTimezone($value, $parameters['TZID']);
        }

        // Local time (floating)
        return $this->parseLocal($value);
    }

    /**
     * Get the data type name
     */
    public function getType(): string
    {
        return 'DATE-TIME';
    }

    /**
     * Check if the value is a valid DATE-TIME format
     */
    public function canParse(string $value): bool
    {
        // Local format: YYYYMMDDTHHMMSS (15 chars)
        // UTC format: YYYYMMDDTHHMMSSZ (16 chars)

        if (str_ends_with($value, 'Z')) {
            // UTC format
            $dateTimePart = substr($value, 0, -1);
            return $this->isValidLocalFormat($dateTimePart);
        }

        // Local format
        return $this->isValidLocalFormat($value);
    }

    /**
     * Parse UTC datetime
     */
    private function parseUtc(string $value): DateTimeImmutable
    {
        // Remove Z suffix
        $dateTimePart = substr($value, 0, -1);

        // Parse components
        $year = (int) substr($dateTimePart, 0, 4);
        $month = (int) substr($dateTimePart, 4, 2);
        $day = (int) substr($dateTimePart, 6, 2);
        $hour = (int) substr($dateTimePart, 9, 2);
        $minute = (int) substr($dateTimePart, 11, 2);
        $second = (int) substr($dateTimePart, 13, 2);

        // Create datetime in UTC
        $dateTimeString = sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $year, $month, $day, $hour, $minute, $second
        );

        $dateTime = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $dateTimeString,
            new DateTimeZone('UTC')
        );

        if ($dateTime === false) {
            throw new ParseException(
                "Invalid DATE-TIME value: '{$value}'",
                ParseException::ERR_INVALID_DATE_TIME
            );
        }

        return $dateTime;
    }

    /**
     * Parse datetime with specific timezone
     */
    private function parseWithTimezone(string $value, string $tzid): DateTimeImmutable
    {
        // Parse components
        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);
        $hour = (int) substr($value, 9, 2);
        $minute = (int) substr($value, 11, 2);
        $second = (int) substr($value, 13, 2);

        // Create datetime string
        $dateTimeString = sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $year, $month, $day, $hour, $minute, $second
        );

        try {
            $timezone = new DateTimeZone($tzid);
        } catch (\Exception $e) {
            throw new ParseException(
                "Invalid timezone: '{$tzid}'",
                ParseException::ERR_INVALID_DATE_TIME
            );
        }

        $dateTime = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $dateTimeString,
            $timezone
        );

        if ($dateTime === false) {
            throw new ParseException(
                "Invalid DATE-TIME value: '{$value}'",
                ParseException::ERR_INVALID_DATE_TIME
            );
        }

        return $dateTime;
    }

    /**
     * Parse local (floating) datetime
     */
    private function parseLocal(string $value): DateTimeImmutable
    {
        // Parse components
        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);
        $hour = (int) substr($value, 9, 2);
        $minute = (int) substr($value, 11, 2);
        $second = (int) substr($value, 13, 2);

        // Create datetime string
        $dateTimeString = sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $year, $month, $day, $hour, $minute, $second
        );

        // Create without timezone (uses default timezone)
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTimeString);

        if ($dateTime === false) {
            throw new ParseException(
                "Invalid DATE-TIME value: '{$value}'",
                ParseException::ERR_INVALID_DATE_TIME
            );
        }

        return $dateTime;
    }

    /**
     * Check if value matches local datetime format (YYYYMMDDTHHMMSS)
     */
    private function isValidLocalFormat(string $value): bool
    {
        // Must be exactly 15 characters
        if (strlen($value) !== 15) {
            return false;
        }

        // Must match pattern: 8 digits, T, 6 digits
        if (!preg_match('/^\d{8}T\d{6}$/', $value)) {
            return false;
        }

        // Extract components
        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);
        $hour = (int) substr($value, 9, 2);
        $minute = (int) substr($value, 11, 2);
        $second = (int) substr($value, 13, 2);

        // Validate date components
        if (!$this->isValidDate($year, $month, $day)) {
            return false;
        }

        // Validate time components
        if ($hour < 0 || $hour > 23) {
            return false;
        }

        if ($minute < 0 || $minute > 59) {
            return false;
        }

        if ($second < 0 || $second > 59) {
            return false;
        }

        return true;
    }

    /**
     * Check if date components form a valid date
     */
    private function isValidDate(int $year, int $month, int $day): bool
    {
        // Check month range
        if ($month < 1 || $month > 12) {
            return false;
        }

        // Get days in month
        $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        // February in leap year
        if ($month === 2 && $this->isLeapYear($year)) {
            $daysInMonth[1] = 29;
        }

        // Check day range
        return $day >= 1 && $day <= $daysInMonth[$month - 1];
    }

    /**
     * Check if year is a leap year
     */
    private function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
