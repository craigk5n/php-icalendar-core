<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use DateTimeImmutable;
use Icalendar\Exception\ParseException;

/**
 * Parser for DATE values according to RFC 5545
 *
 * DATE format: YYYYMMDD
 * Example: 20260205 (February 5, 2026)
 */
class DateParser implements ValueParserInterface
{
    /**
     * Parse a DATE value
     *
     * @param string $value The date string in YYYYMMDD format
     * @param array<string, string> $parameters Property parameters (e.g., TZID)
     * @return DateTimeImmutable The parsed date
     * @throws ParseException if the date format is invalid
     */
    public function parse(string $value, array $parameters = []): DateTimeImmutable
    {
        if (!$this->canParse($value)) {
            throw new ParseException(
                "Invalid DATE format: '{$value}'. Expected YYYYMMDD.",
                ParseException::ERR_INVALID_DATE
            );
        }

        // Parse date components
        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);

        // Create DateTimeImmutable
        $dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $dateString);

        if ($date === false) {
            throw new ParseException(
                "Invalid DATE value: '{$value}'",
                ParseException::ERR_INVALID_DATE
            );
        }

        // Set timezone if TZID parameter is present
        if (isset($parameters['TZID'])) {
            $timezone = new \DateTimeZone($parameters['TZID']);
            $date = $date->setTimezone($timezone);
        }

        // Reset time to midnight (00:00:00)
        return $date->setTime(0, 0, 0);
    }

    /**
     * Get the data type name
     */
    public function getType(): string
    {
        return 'DATE';
    }

    /**
     * Check if the value is a valid DATE format
     */
    public function canParse(string $value): bool
    {
        // Must be exactly 8 digits
        if (!preg_match('/^\d{8}$/', $value)) {
            return false;
        }

        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);

        return $this->isValidDate($year, $month, $day);
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
        // Leap year rules:
        // - Divisible by 4, but not by 100, unless also divisible by 400
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
