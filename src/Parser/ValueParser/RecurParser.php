<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for RECUR values (RRULE) according to RFC 5545 §3.3.10
 */
class RecurParser implements ValueParserInterface
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_RECUR = 'ICAL-TYPE-010';
    public const ERR_INVALID_BY_MODIFIER = 'ICAL-RRULE-004';

    private const FREQ_VALUES = ['SECONDLY', 'MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];

    private const VALID_COMPONENTS = [
        'FREQ', 'COUNT', 'UNTIL', 'INTERVAL', 'BYSECOND', 'BYMINUTE', 'BYHOUR', 
        'BYDAY', 'BYMONTHDAY', 'BYYEARDAY', 'BYWEEKNO', 'BYMONTH', 'BYSETPOS', 'WKST',
    ];

    public function parse(string $value, array $parameters = []): array
    {
        $value = trim($value);
        if ($value === '') {
            throw new ParseException('Empty RECUR value', self::ERR_INVALID_RECUR);
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
            if (empty($part)) continue;
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) {
                throw new ParseException('Invalid RECUR component: ' . $part, self::ERR_INVALID_RECUR);
            }

            $key = strtoupper(trim($kv[0]));
            $val = trim($kv[1]);

            if (!in_array($key, self::VALID_COMPONENTS, true)) {
                if ($this->strict) {
                    throw new ParseException('Invalid RECUR component: ' . $key, self::ERR_INVALID_RECUR);
                }
                continue;
            }
            $rules[$key] = $val;
        }
        return $rules;
    }

    private function validateRules(array $rules): void
    {
        if (!isset($rules['FREQ'])) {
            throw new ParseException('RECUR must have FREQ component', ParseException::ERR_RRULE_FREQ_REQUIRED);
        }

        if (!in_array($rules['FREQ'], self::FREQ_VALUES, true)) {
            throw new ParseException('Invalid RECUR FREQ value: ' . $rules['FREQ'], self::ERR_INVALID_RECUR);
        }

        if (isset($rules['UNTIL']) && isset($rules['COUNT'])) {
            throw new ParseException('RECUR cannot have both UNTIL and COUNT', ParseException::ERR_RRULE_UNTIL_COUNT_EXCLUSIVE);
        }

        if ($this->strict) {
            $this->performStrictValidation($rules);
        }
    }

    private function performStrictValidation(array $rules): void
    {
        if (isset($rules['COUNT'])) {
            if (!ctype_digit($rules['COUNT']) || (int) $rules['COUNT'] <= 0) {
                throw new ParseException('Invalid RECUR COUNT value: ' . $rules['COUNT'], self::ERR_INVALID_RECUR);
            }
        }

        if (isset($rules['INTERVAL'])) {
            if (!ctype_digit($rules['INTERVAL']) || (int) $rules['INTERVAL'] <= 0) {
                throw new ParseException('Invalid RECUR INTERVAL value: ' . $rules['INTERVAL'], ParseException::ERR_RRULE_INVALID_INTERVAL);
            }
        }

        if (isset($rules['BYSECOND'])) $this->validateBySecond($rules['BYSECOND']);
        if (isset($rules['BYMINUTE'])) $this->validateByMinute($rules['BYMINUTE']);
        if (isset($rules['BYHOUR'])) $this->validateByHour($rules['BYHOUR']);
        if (isset($rules['BYDAY'])) $this->validateByDay($rules['BYDAY']);
        if (isset($rules['BYMONTHDAY'])) $this->validateByMonthDay($rules['BYMONTHDAY']);
        if (isset($rules['BYYEARDAY'])) $this->validateByYearDay($rules['BYYEARDAY']);
        if (isset($rules['BYWEEKNO'])) $this->validateByWeekNo($rules['BYWEEKNO']);
        if (isset($rules['BYMONTH'])) $this->validateByMonth($rules['BYMONTH']);
        if (isset($rules['BYSETPOS'])) $this->validateBySetPos($rules['BYSETPOS']);
        if (isset($rules['WKST'])) $this->validateWkst($rules['WKST']);
        if (isset($rules['UNTIL'])) $this->validateUntil($rules['UNTIL']);
    }

    private function validateBySecond(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!ctype_digit($v) || (int)$v < 0 || (int)$v > 60) throw new ParseException("Invalid RECUR BYSECOND value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateByMinute(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!ctype_digit($v) || (int)$v < 0 || (int)$v > 59) throw new ParseException("Invalid RECUR BYMINUTE value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateByHour(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!ctype_digit($v) || (int)$v < 0 || (int)$v > 23) throw new ParseException("Invalid RECUR BYHOUR value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateByDay(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!preg_match('/^([+-]?\d*)?(MO|TU|WE|TH|FR|SA|SU)$/', $v, $matches)) throw new ParseException("Invalid RECUR BYDAY value: $v", self::ERR_INVALID_RECUR);
            if (isset($matches[1]) && $matches[1] !== '' && $matches[1] !== '+' && $matches[1] !== '-') {
                $ord = (int)$matches[1];
                if ($ord === 0 || $ord < -53 || $ord > 53) throw new ParseException("Invalid RECUR BYDAY ordinal: $v", self::ERR_INVALID_RECUR);
            }
        }
    }

    private function validateByMonthDay(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYMONTHDAY value: $v", self::ERR_INVALID_RECUR);
            $iv = (int)$v;
            if ($iv === 0 || $iv < -31 || $iv > 31) throw new ParseException("Invalid RECUR BYMONTHDAY value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateByYearDay(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYYEARDAY value: $v", self::ERR_INVALID_RECUR);
            $iv = (int)$v;
            if ($iv === 0 || $iv < -366 || $iv > 366) throw new ParseException("Invalid RECUR BYYEARDAY value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateByWeekNo(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYWEEKNO value: $v", self::ERR_INVALID_RECUR);
            $iv = (int)$v;
            if ($iv === 0 || $iv < -53 || $iv > 53) throw new ParseException("Invalid RECUR BYWEEKNO value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateByMonth(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!ctype_digit($v) || (int)$v < 1 || (int)$v > 12) throw new ParseException("Invalid RECUR BYMONTH value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateBySetPos(string $value): void {
        foreach (explode(',', $value) as $v) {
            if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYSETPOS value: $v", self::ERR_INVALID_RECUR);
            $iv = (int)$v;
            if ($iv === 0 || $iv < -366 || $iv > 366) throw new ParseException("Invalid RECUR BYSETPOS value: $v", self::ERR_INVALID_RECUR);
        }
    }

    private function validateWkst(string $value): void {
        if (!in_array($value, ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'], true)) throw new ParseException("Invalid RECUR WKST value: $value", self::ERR_INVALID_RECUR);
    }

    private function validateUntil(string $value): void {
        $dtp = new DateTimeParser(); $dp = new DateParser();
        $dtp->setStrict(true); $dp->setStrict(true);
        if (!$dtp->canParse($value) && !$dp->canParse($value)) throw new ParseException("Invalid RECUR UNTIL value: $value", self::ERR_INVALID_RECUR);
    }

    public function getType(): string
    {
        return 'RECUR';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || !str_contains($value, 'FREQ=')) return false;

        $parts = explode(';', $value);
        foreach ($parts as $part) {
            if (empty($part)) continue;
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) return false;
            $key = strtoupper(trim($kv[0]));
            if ($this->strict && !in_array($key, self::VALID_COMPONENTS, true)) return false;
        }
        return true;
    }
}
