<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for DURATION values according to RFC 5545 §3.3.6
 *
 * DURATION format is ISO 8601:
 * - P[n]W - weeks
 * - P[n]D - days
 * - PT[n]H - hours
 * - PT[n]M - minutes
 * - PT[n]S - seconds
 * - Combinations: P[n]DT[n]H[n]M[n]S
 * - Can be negative: -P1D
 *
 * Examples:
 * - P3W (3 weeks)
 * - P3D (3 days)
 * - PT1H (1 hour)
 * - P1DT2H (1 day, 2 hours)
 * - PT30S (30 seconds)
 * - -P1D (negative 1 day)
 */
class DurationParser implements ValueParserInterface
{
    public const ERR_INVALID_DURATION = 'ICAL-TYPE-006';  // Per PRD §6 - DURATION error code

    /**
     * Parse a DURATION value into a DateInterval
     *
     * @param string $value The duration string (e.g., "P3D", "PT1H30M")
     * @param array $parameters Property parameters (unused for DURATION)
     * @return \DateInterval The parsed duration as a DateInterval
     * @throws ParseException if the duration format is invalid
     */
    public function parse(string $value, array $parameters = []): \DateInterval
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty DURATION value',
                self::ERR_INVALID_DURATION
            );
        }

        $isNegative = false;
        if (str_starts_with($value, '-')) {
            $isNegative = true;
            $value = substr($value, 1);
        }

        if (!str_starts_with($value, 'P')) {
            throw new ParseException(
                'Invalid DURATION format: must start with P',
                self::ERR_INVALID_DURATION
            );
        }

        $content = substr($value, 1);

        if ($content === '') {
            throw new ParseException(
                'Invalid DURATION format: missing duration components',
                self::ERR_INVALID_DURATION
            );
        }

        $hasTimeComponent = str_contains($content, 'T');

        $weeks = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        if ($hasTimeComponent) {
            $parts = explode('T', $content, 2);
            $datePart = $parts[0];
            $timePart = $parts[1];
        } else {
            $datePart = $content;
            $timePart = '';
        }

        if ($datePart !== '') {
            $this->parseDatePart($datePart, $weeks, $days);
        }

        if ($timePart !== '') {
            $this->parseTimePart($timePart, $hours, $minutes, $seconds);
        }

        $duration = new \DateInterval('P0D');
        $duration->d = $days + ($weeks * 7);
        $duration->h = $hours;
        $duration->i = $minutes;
        $duration->s = $seconds;

        if ($isNegative) {
            $duration->invert = 1;
        }

        return $duration;
    }

    private function parseDatePart(string $datePart, int &$weeks, int &$days): void
    {
        $pattern = '/^(\d+)([DW])$/';
        $matches = [];

        if (!preg_match($pattern, $datePart, $matches)) {
            throw new ParseException(
                'Invalid DURATION date component: ' . $datePart,
                self::ERR_INVALID_DURATION
            );
        }

        $value = (int) $matches[1];
        $unit = $matches[2];

        if ($unit === 'W') {
            $weeks = $value;
        } elseif ($unit === 'D') {
            $days = $value;
        }
    }

    private function parseTimePart(string $timePart, int &$hours, int &$minutes, int &$seconds): void
    {
        $pattern = '/^(\d+)H(\d+)M(\d+)S$/';
        $matches = [];

        if (preg_match($pattern, $timePart, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            $seconds = (int) $matches[3];
            return;
        }

        $pattern = '/^(\d+)H(\d+)M$/';
        if (preg_match($pattern, $timePart, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            $seconds = 0;
            return;
        }

        $pattern = '/^(\d+)H$/';
        if (preg_match($pattern, $timePart, $matches)) {
            $hours = (int) $matches[1];
            $minutes = 0;
            $seconds = 0;
            return;
        }

        $pattern = '/^(\d+)M(\d+)S$/';
        if (preg_match($pattern, $timePart, $matches)) {
            $hours = 0;
            $minutes = (int) $matches[1];
            $seconds = (int) $matches[2];
            return;
        }

        $pattern = '/^(\d+)M$/';
        if (preg_match($pattern, $timePart, $matches)) {
            $hours = 0;
            $minutes = (int) $matches[1];
            $seconds = 0;
            return;
        }

        $pattern = '/^(\d+)S$/';
        if (preg_match($pattern, $timePart, $matches)) {
            $hours = 0;
            $minutes = 0;
            $seconds = (int) $matches[1];
            return;
        }

        throw new ParseException(
            'Invalid DURATION time component: ' . $timePart,
            self::ERR_INVALID_DURATION
        );
    }

    /**
     * Get the data type name
     */
    public function getType(): string
    {
        return 'DURATION';
    }

    /**
     * Check if the value is a valid DURATION format
     */
    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        $isNegative = str_starts_with($value, '-');
        if ($isNegative) {
            $value = substr($value, 1);
        }

        if (!str_starts_with($value, 'P')) {
            return false;
        }

        $content = substr($value, 1);

        if ($content === '') {
            return false;
        }

        return $this->isValidDurationContent($content);
    }

    private function isValidDurationContent(string $content): bool
    {
        $hasTimeComponent = str_contains($content, 'T');

        if ($hasTimeComponent) {
            $parts = explode('T', $content, 2);
            $datePart = $parts[0];
            $timePart = $parts[1];
        } else {
            $datePart = $content;
            $timePart = '';
        }

        if ($datePart !== '') {
            if (!$this->isValidDatePart($datePart)) {
                return false;
            }
        }

        if ($timePart !== '') {
            if (!$this->isValidTimePart($timePart)) {
                return false;
            }
        }

        return true;
    }

    private function isValidDatePart(string $datePart): bool
    {
        $pattern = '/^(\d+)[DW]$/';
        return (bool) preg_match($pattern, $datePart);
    }

    private function isValidTimePart(string $timePart): bool
    {
        $patterns = [
            '/^\d+H\d+M\d+S$/',
            '/^\d+H\d+M$/',
            '/^\d+H$/',
            '/^\d+M\d+S$/',
            '/^\d+M$/',
            '/^\d+S$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $timePart)) {
                return true;
            }
        }

        return false;
    }
}
