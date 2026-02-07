<?php

declare(strict_types=1);

namespace Icalendar\Recurrence;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\DateParser;
use Icalendar\Parser\ValueParser\DateTimeParser;

/**
 * Parser for RRULE strings according to RFC 5545 §3.3.10
 */
class RRuleParser
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    private const VALID_KEYS = [
        'FREQ', 'INTERVAL', 'COUNT', 'UNTIL', 'WKST', 'BYSECOND', 'BYMINUTE', 
        'BYHOUR', 'BYDAY', 'BYMONTHDAY', 'BYYEARDAY', 'BYWEEKNO', 'BYMONTH', 'BYSETPOS'
    ];

    public function parse(string $data): RRule
    {
        if (empty($data)) {
            throw new ParseException('Empty RRULE string', ParseException::ERR_RRULE_INVALID_FORMAT);
        }

        $parts = explode(';', $data);
        $rules = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) {
                throw new ParseException("Invalid RRULE component: {$part}", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
            
            $key = strtoupper(trim($kv[0]));
            $value = trim($kv[1]);

            if ($this->strict && !in_array($key, self::VALID_KEYS, true)) {
                throw new ParseException("Unknown parameter found in RRULE: {$key}", ParseException::ERR_RRULE_INVALID_FORMAT);
            }

            $rules[$key] = $value;
        }

        if ($this->strict) {
            $this->validateRules($rules);
        }

        return $this->buildRRule($rules);
    }

    private function validateRules(array $rules): void
    {
        if (!isset($rules['FREQ'])) {
            throw new ParseException('RRULE must have FREQ component', ParseException::ERR_RRULE_FREQ_REQUIRED);
        }

        $freq = strtoupper($rules['FREQ']);
        if (!in_array($freq, ['SECONDLY', 'MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'], true)) {
            throw new ParseException("Invalid RECUR FREQ value: {$freq}", ParseException::ERR_RRULE_INVALID_FORMAT);
        }

        if (isset($rules['COUNT'])) {
            if (!ctype_digit($rules['COUNT']) || (int) $rules['COUNT'] <= 0) {
                throw new ParseException("Invalid RECUR COUNT value: {$rules['COUNT']}", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['INTERVAL'])) {
            if (!ctype_digit($rules['INTERVAL']) || (int) $rules['INTERVAL'] <= 0) {
                throw new ParseException("Invalid RECUR INTERVAL value: {$rules['INTERVAL']}", ParseException::ERR_RRULE_INVALID_INTERVAL);
            }
        }

        if (isset($rules['BYSECOND'])) {
            foreach (explode(',', $rules['BYSECOND']) as $v) {
                if ($v === '') continue;
                if (!ctype_digit($v) || (int)$v < 0 || (int)$v > 60) throw new ParseException("Invalid RECUR BYSECOND value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['BYMINUTE'])) {
            foreach (explode(',', $rules['BYMINUTE']) as $v) {
                if ($v === '') continue;
                if (!ctype_digit($v) || (int)$v < 0 || (int)$v > 59) throw new ParseException("Invalid RECUR BYMINUTE value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['BYHOUR'])) {
            foreach (explode(',', $rules['BYHOUR']) as $v) {
                if ($v === '') continue;
                if (!ctype_digit($v) || (int)$v < 0 || (int)$v > 23) throw new ParseException("Invalid RECUR BYHOUR value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['BYMONTHDAY'])) {
            foreach (explode(',', $rules['BYMONTHDAY']) as $v) {
                if ($v === '') continue;
                if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYMONTHDAY value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
                $iv = (int)$v;
                if ($iv === 0 || $iv < -31 || $iv > 31) throw new ParseException("Invalid RECUR BYMONTHDAY value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['BYYEARDAY'])) {
            foreach (explode(',', $rules['BYYEARDAY']) as $v) {
                if ($v === '') continue;
                if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYYEARDAY value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
                $iv = (int)$v;
                if ($iv === 0 || $iv < -366 || $iv > 366) throw new ParseException("Invalid RECUR BYYEARDAY value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['BYWEEKNO'])) {
            foreach (explode(',', $rules['BYWEEKNO']) as $v) {
                if ($v === '') continue;
                if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYWEEKNO value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
                $iv = (int)$v;
                if ($iv === 0 || $iv < -53 || $iv > 53) throw new ParseException("Invalid RECUR BYWEEKNO value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['BYMONTH'])) {
            foreach (explode(',', $rules['BYMONTH']) as $v) {
                if ($v === '') continue;
                if (!ctype_digit($v) || (int)$v < 1 || (int)$v > 12) throw new ParseException("Invalid RECUR BYMONTH value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['BYSETPOS'])) {
            foreach (explode(',', $rules['BYSETPOS']) as $v) {
                if ($v === '') continue;
                if (!preg_match('/^[+-]?\d+$/', $v)) throw new ParseException("Invalid RECUR BYSETPOS value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
                $iv = (int)$v;
                if ($iv === 0 || $iv < -366 || $iv > 366) throw new ParseException("Invalid RECUR BYSETPOS value: $v", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }

        if (isset($rules['WKST'])) {
            if (!in_array(strtoupper($rules['WKST']), ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'], true)) {
                throw new ParseException("Invalid RECUR WKST value: {$rules['WKST']}", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
        }
    }

    private function buildRRule(array $rules): RRule
    {
        if (!isset($rules['FREQ'])) {
            throw new ParseException('RRULE must have FREQ component', ParseException::ERR_RRULE_FREQ_REQUIRED);
        }

        $freq = strtoupper($rules['FREQ']);
        $interval = isset($rules['INTERVAL']) ? (int) $rules['INTERVAL'] : 1;
        $count = isset($rules['COUNT']) ? (int) $rules['COUNT'] : null;
        
        $until = null;
        $untilIsDate = false;
        if (isset($rules['UNTIL'])) {
            $until = $this->parseUntil($rules['UNTIL'], $untilIsDate);
        }

        $wkst = isset($rules['WKST']) ? strtoupper($rules['WKST']) : 'MO';

        if ($until !== null && $count !== null) {
            throw new ParseException('RRULE cannot have both UNTIL and COUNT', ParseException::ERR_RRULE_UNTIL_COUNT_EXCLUSIVE);
        }

        // Parse BY* components directly
        $bySecond = !isset($rules['BYSECOND']) || $rules['BYSECOND'] === '' ? [] : array_map('intval', explode(',', $rules['BYSECOND']));
        $byMinute = !isset($rules['BYMINUTE']) || $rules['BYMINUTE'] === '' ? [] : array_map('intval', explode(',', $rules['BYMINUTE']));
        $byHour = !isset($rules['BYHOUR']) || $rules['BYHOUR'] === '' ? [] : array_map('intval', explode(',', $rules['BYHOUR']));
        
        $byDay = [];
        if (isset($rules['BYDAY']) && !empty($rules['BYDAY'])) {
            $parts = explode(',', $rules['BYDAY']);
            foreach ($parts as $part) {
                if (preg_match('/^([+-]?\d+)?(MO|TU|WE|TH|FR|SA|SU)$/i', $part, $matches)) {
                    $ordinal = (isset($matches[1]) && $matches[1] !== '' && $matches[1] !== '+' && $matches[1] !== '-') ? (int) $matches[1] : null;
                    if ($this->strict && $ordinal === 0) {
                        throw new ParseException("Invalid BYDAY ordinal: {$part}", ParseException::ERR_RRULE_INVALID_FORMAT);
                    }
                    $byDay[] = [
                        'day' => strtoupper($matches[2]),
                        'ordinal' => $ordinal,
                    ];
                }
            }
        }

        $byMonthDay = !isset($rules['BYMONTHDAY']) || $rules['BYMONTHDAY'] === '' ? [] : array_map('intval', explode(',', $rules['BYMONTHDAY']));
        $byYearDay = !isset($rules['BYYEARDAY']) || $rules['BYYEARDAY'] === '' ? [] : array_map('intval', explode(',', $rules['BYYEARDAY']));
        $byWeekNo = !isset($rules['BYWEEKNO']) || $rules['BYWEEKNO'] === '' ? [] : array_map('intval', explode(',', $rules['BYWEEKNO']));
        $byMonth = !isset($rules['BYMONTH']) || $rules['BYMONTH'] === '' ? [] : array_map('intval', explode(',', $rules['BYMONTH']));
        $bySetPos = !isset($rules['BYSETPOS']) || $rules['BYSETPOS'] === '' ? [] : array_map('intval', explode(',', $rules['BYSETPOS']));

        return new RRule(
            $freq,
            $interval,
            $count,
            $until,
            $bySecond,
            $byMinute,
            $byHour,
            $byDay,
            $byMonthDay,
            $byYearDay,
            $byWeekNo,
            $byMonth,
            $bySetPos,
            $wkst,
            $untilIsDate
        );
    }

    private function parseUntil(string $value, bool &$isDate): \DateTimeImmutable
    {
        $dateParser = new DateParser();
        $dateTimeParser = new DateTimeParser();
        
        $dateParser->setStrict($this->strict);
        $dateTimeParser->setStrict($this->strict);

        if ($dateTimeParser->canParse($value)) {
            $isDate = false;
            return $dateTimeParser->parse($value);
        }
        
        if ($dateParser->canParse($value)) {
            $isDate = true;
            return $dateParser->parse($value);
        }
        
        throw new ParseException("Invalid RRULE UNTIL value: {$value}", ParseException::ERR_RRULE_INVALID_UNTIL);
    }

    public function canParse(string $data): bool
    {
        if (empty($data) || !str_contains($data, 'FREQ=')) return false;
        
        $parts = explode(';', $data);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) return false;
            
            if ($this->strict && !in_array(strtoupper(trim($kv[0])), self::VALID_KEYS, true)) {
                return false;
            }
        }
        return true;
    }
}
