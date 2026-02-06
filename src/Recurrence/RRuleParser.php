<?php

declare(strict_types=1);

namespace Icalendar\Recurrence;

use Icalendar\Exception\ParseException;
use Icalendar\Parser\ValueParser\DateTimeParser;
use Icalendar\Parser\ValueParser\RecurParser;

/**
 * Parser for RRULE (recurrence rules) that returns immutable RRule objects
 *
 * This is a wrapper around RecurParser that converts the array output
 * into a strongly-typed, immutable RRule value object.
 *
 * @see RRule
 * @see RecurParser
 */
class RRuleParser
{
    private RecurParser $recurParser;
    private DateTimeParser $dateTimeParser;

    public function __construct()
    {
        $this->recurParser = new RecurParser();
        $this->dateTimeParser = new DateTimeParser();
    }

    /**
     * Parse an RRULE string into an immutable RRule object
     *
     * @param string $rrule The RRULE string (e.g., "FREQ=DAILY;COUNT=10")
     * @return RRule The parsed, immutable recurrence rule
     * @throws ParseException If the RRULE is invalid
     */
    public function parse(string $rrule): RRule
    {
        $rules = $this->recurParser->parse($rrule);

        return $this->buildRRule($rules);
    }

    /**
     * Check if a string can be parsed as an RRULE
     *
     * @param string $rrule The string to check
     * @return bool True if the string is a valid RRULE format
     */
    public function canParse(string $rrule): bool
    {
        return $this->recurParser->canParse($rrule);
    }

    /**
     * Build an RRule object from the parsed rules array
     *
     * @param array<string, string> $rules
     * @return RRule
     */
    private function buildRRule(array $rules): RRule
    {
        $freq = $rules['FREQ'];
        $interval = isset($rules['INTERVAL']) ? (int) $rules['INTERVAL'] : 1;
        $count = isset($rules['COUNT']) ? (int) $rules['COUNT'] : null;
        $until = isset($rules['UNTIL']) ? $this->parseUntil($rules['UNTIL']) : null;
        $wkst = $rules['WKST'] ?? 'MO';

        // Parse BY* components
        $bySecond = isset($rules['BYSECOND']) ? $this->parseIntList($rules['BYSECOND']) : [];
        $byMinute = isset($rules['BYMINUTE']) ? $this->parseIntList($rules['BYMINUTE']) : [];
        $byHour = isset($rules['BYHOUR']) ? $this->parseIntList($rules['BYHOUR']) : [];
        $byDay = isset($rules['BYDAY']) ? $this->parseByDay($rules['BYDAY']) : [];
        $byMonthDay = isset($rules['BYMONTHDAY']) ? $this->parseIntList($rules['BYMONTHDAY']) : [];
        $byYearDay = isset($rules['BYYEARDAY']) ? $this->parseIntList($rules['BYYEARDAY']) : [];
        $byWeekNo = isset($rules['BYWEEKNO']) ? $this->parseIntList($rules['BYWEEKNO']) : [];
        $byMonth = isset($rules['BYMONTH']) ? $this->parseIntList($rules['BYMONTH']) : [];
        $bySetPos = isset($rules['BYSETPOS']) ? $this->parseIntList($rules['BYSETPOS']) : [];

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
            $wkst
        );
    }

    /**
     * Parse the UNTIL value into a DateTimeImmutable
     *
     * @param string $until
     * @return \DateTimeImmutable
     */
    private function parseUntil(string $until): \DateTimeImmutable
    {
        // Try to parse as DATE-TIME (has 'T')
        if (str_contains($until, 'T')) {
            return $this->dateTimeParser->parse($until);
        }

        // Parse as DATE (YYYYMMDD)
        if (strlen($until) === 8) {
            return $this->dateTimeParser->parse($until . 'T000000');
        }

        throw new ParseException(
            'Invalid UNTIL value: ' . $until,
            RecurParser::ERR_INVALID_RECUR
        );
    }

    /**
     * Parse a comma-separated list of integers
     *
     * @param string $value
     * @return array<int>
     */
    private function parseIntList(string $value): array
    {
        $parts = explode(',', $value);
        return array_map('intval', $parts);
    }

    /**
     * Parse BYDAY values into structured format
     *
     * Format: "MO" or "2TU" or "-1FR"
     *
     * @param string $value
     * @return array<array{day: string, ordinal: int|null}>
     */
    private function parseByDay(string $value): array
    {
        $days = explode(',', $value);
        $result = [];

        foreach ($days as $day) {
            // Match pattern: optional ordinal (+-n) followed by day abbreviation
            if (preg_match('/^([+-]?\d+)?(MO|TU|WE|TH|FR|SA|SU)$/', $day, $matches)) {
                $ordinal = null;
                if (isset($matches[1]) && $matches[1] !== '') {
                    $ordinal = (int) $matches[1];
                }
                $result[] = [
                    'day' => $matches[2],
                    'ordinal' => $ordinal,
                ];
            }
        }

        return $result;
    }
}