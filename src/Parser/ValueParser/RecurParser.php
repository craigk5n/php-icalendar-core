<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for RECUR values (RRULE) according to RFC 5545 §3.3.10
 *
 * RECUR defines a recurrence rule pattern.
 * Format: FREQ=...;COUNT=...;UNTIL=...;INTERVAL=...;BYDAY=...;BYMONTH=...;etc.
 */
class RecurParser implements ValueParserInterface
{
    public const ERR_INVALID_RECUR = 'ICAL-TYPE-010';

    private const FREQ_VALUES = ['SECONDLY', 'MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];

    private const VALID_COMPONENTS = [
        'FREQ',
        'COUNT',
        'UNTIL',
        'INTERVAL',
        'BYSECOND',
        'BYMINUTE',
        'BYHOUR',
        'BYDAY',
        'BYMONTHDAY',
        'BYYEARDAY',
        'BYWEEKNO',
        'BYMONTH',
        'BYSETPOS',
        'WKST',
    ];

    public function parse(string $value, array $parameters = []): array
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty RECUR value',
                self::ERR_INVALID_RECUR
            );
        }

        $rules = $this->parseRruleString($value);

        $this->validateRules($rules);

        return $rules;
    }

    private function parseRruleString(string $value): array
    {
        $parts = explode(';', $value);
        $rules = [];

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $kv = explode('=', $part, 2);

            if (count($kv) !== 2) {
                throw new ParseException(
                    'Invalid RECUR component: ' . $part,
                    self::ERR_INVALID_RECUR
                );
            }

            $key = strtoupper(trim($kv[0]));
            $val = trim($kv[1]);

            if (!in_array($key, self::VALID_COMPONENTS, true)) {
                throw new ParseException(
                    'Invalid RECUR component: ' . $key,
                    self::ERR_INVALID_RECUR
                );
            }

            $rules[$key] = $val;
        }

        return $rules;
    }

    private function validateRules(array $rules): void
    {
        if (!isset($rules['FREQ'])) {
            throw new ParseException(
                'RECUR must have FREQ component',
                self::ERR_INVALID_RECUR
            );
        }

        if (!in_array($rules['FREQ'], self::FREQ_VALUES, true)) {
            throw new ParseException(
                'Invalid RECUR FREQ value: ' . $rules['FREQ'],
                self::ERR_INVALID_RECUR
            );
        }

        // UNTIL and COUNT are mutually exclusive per RFC 5545 §3.3.10
        if (isset($rules['UNTIL']) && isset($rules['COUNT'])) {
            throw new ParseException(
                'RECUR cannot have both UNTIL and COUNT',
                self::ERR_INVALID_RECUR
            );
        }

        if (isset($rules['COUNT'])) {
            if (!ctype_digit($rules['COUNT']) || (int) $rules['COUNT'] <= 0) {
                throw new ParseException(
                    'Invalid RECUR COUNT value: ' . $rules['COUNT'] . ' (must be positive integer)',
                    self::ERR_INVALID_RECUR
                );
            }
        }

        if (isset($rules['INTERVAL'])) {
            if (!ctype_digit($rules['INTERVAL']) || (int) $rules['INTERVAL'] <= 0) {
                throw new ParseException(
                    'Invalid RECUR INTERVAL value: ' . $rules['INTERVAL'] . ' (must be positive integer)',
                    self::ERR_INVALID_RECUR
                );
            }
        }

        if (isset($rules['BYSECOND'])) {
            $this->validateBySecond($rules['BYSECOND']);
        }

        if (isset($rules['BYMINUTE'])) {
            $this->validateByMinute($rules['BYMINUTE']);
        }

        if (isset($rules['BYHOUR'])) {
            $this->validateByHour($rules['BYHOUR']);
        }

        if (isset($rules['BYDAY'])) {
            $this->validateByDay($rules['BYDAY'], $rules['FREQ']);
        }

        if (isset($rules['BYMONTHDAY'])) {
            $this->validateByMonthDay($rules['BYMONTHDAY']);
        }

        if (isset($rules['BYYEARDAY'])) {
            $this->validateByYearDay($rules['BYYEARDAY']);
        }

        if (isset($rules['BYWEEKNO'])) {
            $this->validateByWeekNo($rules['BYWEEKNO']);
        }

        if (isset($rules['BYMONTH'])) {
            $this->validateByMonth($rules['BYMONTH']);
        }

        if (isset($rules['BYSETPOS'])) {
            $this->validateBySetPos($rules['BYSETPOS']);
        }

        if (isset($rules['WKST'])) {
            $this->validateWkst($rules['WKST']);
        }

        if (isset($rules['UNTIL'])) {
            $this->validateUntil($rules['UNTIL']);
        }
    }

    private function validateBySecond(string $value): void
    {
        $seconds = explode(',', $value);

        foreach ($seconds as $second) {
            if (!ctype_digit($second) || (int) $second < 0 || (int) $second > 60) {
                throw new ParseException(
                    'Invalid RECUR BYSECOND value: ' . $second . ' (must be 0-60)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateByMinute(string $value): void
    {
        $minutes = explode(',', $value);

        foreach ($minutes as $minute) {
            if (!ctype_digit($minute) || (int) $minute < 0 || (int) $minute > 59) {
                throw new ParseException(
                    'Invalid RECUR BYMINUTE value: ' . $minute . ' (must be 0-59)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateByHour(string $value): void
    {
        $hours = explode(',', $value);

        foreach ($hours as $hour) {
            if (!ctype_digit($hour) || (int) $hour < 0 || (int) $hour > 23) {
                throw new ParseException(
                    'Invalid RECUR BYHOUR value: ' . $hour . ' (must be 0-23)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateByDay(string $value, string $freq): void
    {
        $days = explode(',', $value);

        foreach ($days as $day) {
            // Pattern: optional +/- number (ordinal) followed by day abbreviation
            if (!preg_match('/^([+-]?\d*)?(MO|TU|WE|TH|FR|SA|SU)$/', $day, $matches)) {
                throw new ParseException(
                    'Invalid RECUR BYDAY value: ' . $day,
                    self::ERR_INVALID_RECUR
                );
            }

            // Validate ordinal range if present (must be ±1 to ±53)
            // Note: isset and !== '' because empty('0') is true in PHP
            if (isset($matches[1]) && $matches[1] !== '') {
                $ordinal = (int) $matches[1];
                if ($ordinal === 0 || $ordinal < -53 || $ordinal > 53) {
                    throw new ParseException(
                        'Invalid RECUR BYDAY ordinal: ' . $matches[1] . ' (must be ±1 to ±53)',
                        self::ERR_INVALID_RECUR
                    );
                }
            }
        }
    }

    private function validateByMonthDay(string $value): void
    {
        $days = explode(',', $value);

        foreach ($days as $day) {
            // Handle negative numbers correctly
            if (!preg_match('/^-?\d+$/', $day)) {
                throw new ParseException(
                    'Invalid RECUR BYMONTHDAY value: ' . $day . ' (must be 1-31 or -31 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }

            $dayInt = (int) $day;
            if ($dayInt < -31 || $dayInt > 31 || $dayInt === 0) {
                throw new ParseException(
                    'Invalid RECUR BYMONTHDAY value: ' . $day . ' (must be 1-31 or -31 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateByYearDay(string $value): void
    {
        $days = explode(',', $value);

        foreach ($days as $day) {
            if (!preg_match('/^-?\d+$/', $day)) {
                throw new ParseException(
                    'Invalid RECUR BYYEARDAY value: ' . $day . ' (must be 1-366 or -366 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }

            $dayInt = (int) $day;
            if ($dayInt < -366 || $dayInt > 366 || $dayInt === 0) {
                throw new ParseException(
                    'Invalid RECUR BYYEARDAY value: ' . $day . ' (must be 1-366 or -366 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateByWeekNo(string $value): void
    {
        $weeks = explode(',', $value);

        foreach ($weeks as $week) {
            if (!preg_match('/^-?\d+$/', $week)) {
                throw new ParseException(
                    'Invalid RECUR BYWEEKNO value: ' . $week . ' (must be 1-53 or -53 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }

            $weekInt = (int) $week;
            if ($weekInt < -53 || $weekInt > 53 || $weekInt === 0) {
                throw new ParseException(
                    'Invalid RECUR BYWEEKNO value: ' . $week . ' (must be 1-53 or -53 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateByMonth(string $value): void
    {
        $months = explode(',', $value);

        foreach ($months as $month) {
            if (!ctype_digit($month) || (int) $month < 1 || (int) $month > 12) {
                throw new ParseException(
                    'Invalid RECUR BYMONTH value: ' . $month . ' (must be 1-12)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateBySetPos(string $value): void
    {
        $positions = explode(',', $value);

        foreach ($positions as $pos) {
            if (!preg_match('/^-?\d+$/', $pos)) {
                throw new ParseException(
                    'Invalid RECUR BYSETPOS value: ' . $pos . ' (must be 1-366 or -366 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }

            $posInt = (int) $pos;
            if ($posInt < -366 || $posInt > 366 || $posInt === 0) {
                throw new ParseException(
                    'Invalid RECUR BYSETPOS value: ' . $pos . ' (must be 1-366 or -366 to -1)',
                    self::ERR_INVALID_RECUR
                );
            }
        }
    }

    private function validateWkst(string $value): void
    {
        if (!preg_match('/^(MO|TU|WE|TH|FR|SA|SU)$/', $value)) {
            throw new ParseException(
                'Invalid RECUR WKST value: ' . $value,
                self::ERR_INVALID_RECUR
            );
        }
    }

    private function validateUntil(string $value): void
    {
        $dateTimeParser = new DateTimeParser();

        if (!$dateTimeParser->canParse($value)) {
            throw new ParseException(
                'Invalid RECUR UNTIL value: ' . $value,
                self::ERR_INVALID_RECUR
            );
        }
    }

    public function getType(): string
    {
        return 'RECUR';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (!str_contains($value, 'FREQ=')) {
            return false;
        }

        $parts = explode(';', $value);

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            $kv = explode('=', $part, 2);

            if (count($kv) !== 2) {
                return false;
            }

            $key = strtoupper(trim($kv[0]));

            if (!in_array($key, self::VALID_COMPONENTS, true)) {
                return false;
            }
        }

        return true;
    }
}
