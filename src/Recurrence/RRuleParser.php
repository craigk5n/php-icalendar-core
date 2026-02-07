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

    public function parse(string $data): RRule
    {
        if (empty($data)) {
            throw new ParseException('Empty RRULE string', ParseException::ERR_RRULE_INVALID_FORMAT);
        }

        $parts = explode(';', $data);
        $rules = [];

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) {
                throw new ParseException("Invalid RRULE component: {$part}", ParseException::ERR_RRULE_INVALID_FORMAT);
            }
            $key = strtoupper(trim($kv[0]));
            $value = trim($kv[1]);
            $rules[$key] = $value;
        }

        return $this->buildRRule($rules);
    }

    private function buildRRule(array $rules): RRule
    {
        if (!isset($rules['FREQ'])) {
            throw new ParseException('RRULE must have FREQ component', ParseException::ERR_RRULE_FREQ_REQUIRED);
        }

        $freq = strtoupper($rules['FREQ']);
        $interval = isset($rules['INTERVAL']) ? (int) $rules['INTERVAL'] : 1;
        $count = isset($rules['COUNT']) ? (int) $rules['COUNT'] : null;
        $until = isset($rules['UNTIL']) ? $this->parseUntil($rules['UNTIL']) : null;
        $wkst = isset($rules['WKST']) ? strtoupper($rules['WKST']) : 'MO';

        if ($until !== null && $count !== null) {
            throw new ParseException('RRULE cannot have both UNTIL and COUNT', ParseException::ERR_RRULE_UNTIL_COUNT_EXCLUSIVE);
        }

        // Parse BY* components directly, ensuring empty arrays are passed if value is empty or missing.
        // This explicitly handles cases where a BY* parameter is present but empty (e.g., BYSECOND=).
        $bySecond = !isset($rules['BYSECOND']) || $rules['BYSECOND'] === '' ? [] : array_map('intval', explode(',', $rules['BYSECOND']));
        $byMinute = !isset($rules['BYMINUTE']) || $rules['BYMINUTE'] === '' ? [] : array_map('intval', explode(',', $rules['BYMINUTE']));
        $byHour = !isset($rules['BYHOUR']) || $rules['BYHOUR'] === '' ? [] : array_map('intval', explode(',', $rules['BYHOUR']));
        
        $byDay = [];
        if (isset($rules['BYDAY']) && !empty($rules['BYDAY'])) {
            $parts = explode(',', $rules['BYDAY']);
            foreach ($parts as $part) {
                if (preg_match('/^([+-]?\d+)?(MO|TU|WE|TH|FR|SA|SU)$/i', $part, $matches)) {
                    $byDay[] = [
                        'day' => strtoupper($matches[2]),
                        'ordinal' => (isset($matches[1]) && $matches[1] !== '' && $matches[1] !== '+' && $matches[1] !== '-') ? (int) $matches[1] : null,
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
            $wkst,
            $bySecond,
            $byMinute,
            $byHour,
            $byDay,
            $byMonthDay,
            $byYearDay,
            $byWeekNo,
            $byMonth,
            $bySetPos
        );
    }

    private function parseUntil(string $value): \DateTimeImmutable
    {
        $dateParser = new DateParser();
        $dateTimeParser = new DateTimeParser();
        if ($dateTimeParser->canParse($value)) return $dateTimeParser->parse($value);
        if ($dateParser->canParse($value)) return $dateParser->parse($value);
        throw new ParseException("Invalid RRULE UNTIL value: {$value}", ParseException::ERR_RRULE_INVALID_UNTIL);
    }

    public function canParse(string $data): bool
    {
        if (empty($data) || !str_contains($data, 'FREQ=')) return false;
        $validKeys = ['FREQ', 'INTERVAL', 'COUNT', 'UNTIL', 'WKST', 'BYSECOND', 'BYMINUTE', 'BYHOUR', 'BYDAY', 'BYMONTHDAY', 'BYYEARDAY', 'BYWEEKNO', 'BYMONTH', 'BYSETPOS'];
        $parts = explode(';', $data);
        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) return false;
            if (!in_array(strtoupper(trim($kv[0])), $validKeys, true)) {
                if ($this->strict) return false;
            }
        }
        return true;
    }
}
