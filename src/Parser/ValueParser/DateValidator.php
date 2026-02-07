<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

/**
 * Shared date validation utility for DateParser and DateTimeParser
 */
class DateValidator
{
    /**
     * Check if date components form a valid date
     */
    public static function isValidDate(int $year, int $month, int $day): bool
    {
        if ($month < 1 || $month > 12) {
            return false;
        }

        $daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        if ($month === 2 && self::isLeapYear($year)) {
            $daysInMonth[1] = 29;
        }

        return $day >= 1 && $day <= $daysInMonth[$month - 1];
    }

    /**
     * Check if year is a leap year
     */
    public static function isLeapYear(int $year): bool
    {
        if ($year === 0) {
            return false;
        }
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
