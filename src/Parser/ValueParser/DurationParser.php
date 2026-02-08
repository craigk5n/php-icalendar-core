<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for DURATION values according to RFC 5545 §3.3.6
 */
class DurationParser implements ValueParserInterface
{
    #[\Override]
    public function setStrict(bool $strict): void
    {
        // Not used in DurationParser
    }

    public const ERR_INVALID_DURATION = 'ICAL-TYPE-006';

    /**
     * Parse a DURATION value into a DateInterval
     *
     * @param string $value The duration string (e.g., "P3D", "PT1H30M")
     * @param array<string, string> $parameters Property parameters (unused for DURATION)
     * @return \DateInterval The parsed duration as a DateInterval
     * @throws ParseException if the duration format is invalid
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): \DateInterval
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException('Empty DURATION value', self::ERR_INVALID_DURATION);
        }

        $isNegative = false;
        if (str_starts_with($value, '-')) {
            $isNegative = true;
            $value = substr($value, 1);
        }

        if (!str_starts_with($value, 'P')) {
            throw new ParseException('Invalid DURATION format: must start with P', self::ERR_INVALID_DURATION);
        }

        $content = substr($value, 1);

        if ($content === '') {
            throw new ParseException('Invalid DURATION format: missing duration components', self::ERR_INVALID_DURATION);
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
            $timePart = $parts[1] ?? '';
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

        $duration = new \DateInterval(sprintf(
            'P%dDT%dH%dM%dS',
            $days + ($weeks * 7),
            $hours,
            $minutes,
            $seconds
        ));

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
            throw new ParseException('Invalid DURATION date component: ' . $datePart, self::ERR_INVALID_DURATION);
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
        $patterns = [
            '/^(\d+)H(\d+)M(\d+)S$/' => function($m) use (&$hours, &$minutes, &$seconds) { $hours = (int)$m[1]; $minutes = (int)$m[2]; $seconds = (int)$m[3]; },
            '/^(\d+)H(\d+)M$/' => function($m) use (&$hours, &$minutes) { $hours = (int)$m[1]; $minutes = (int)$m[2]; },
            '/^(\d+)H$/' => function($m) use (&$hours) { $hours = (int)$m[1]; },
            '/^(\d+)M(\d+)S$/' => function($m) use (&$minutes, &$seconds) { $minutes = (int)$m[1]; $seconds = (int)$m[2]; },
            '/^(\d+)M$/' => function($m) use (&$minutes) { $minutes = (int)$m[1]; },
            '/^(\d+)S$/' => function($m) use (&$seconds) { $seconds = (int)$m[1]; }
        ];

        foreach ($patterns as $pattern => $callback) {
            if (preg_match($pattern, $timePart, $matches)) {
                $callback($matches);
                return;
            }
        }

        throw new ParseException('Invalid DURATION time component: ' . $timePart, self::ERR_INVALID_DURATION);
    }

    #[\Override]
    public function getType(): string
    {
        return 'DURATION';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        $value = trim($value);
        if ($value === '') return false;
        if (str_starts_with($value, '-')) $value = substr($value, 1);
        if (!str_starts_with($value, 'P')) return false;
        $content = substr($value, 1);
        if ($content === '') return false;

        $hasTimeComponent = str_contains($content, 'T');
        if ($hasTimeComponent) {
            $parts = explode('T', $content, 2);
            $datePart = $parts[0];
            $timePart = $parts[1] ?? '';
        } else {
            $datePart = $content;
            $timePart = '';
        }

        if ($datePart !== '' && !preg_match('/^(\d+)[DW]$/', $datePart)) return false;
        if ($timePart !== '') {
            $validTime = false;
            $patterns = ['/^\d+H\d+M\d+S$/', '/^\d+H\d+M$/', '/^\d+H$/', '/^\d+M\d+S$/', '/^\d+M$/', '/^\d+S$/'];
            foreach ($patterns as $p) {
                if (preg_match($p, $timePart)) {
                    $validTime = true;
                    break;
                }
            }
            if (!$validTime) return false;
        }

        return true;
    }
}