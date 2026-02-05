<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for PERIOD values according to RFC 5545 §3.3.9
 *
 * PERIOD format is "date-time "/" date-time / duration"
 * Example: "19970101T230000Z/19970102T010000Z" or "19970101T230000Z/PT2H"
 */
class PeriodParser implements ValueParserInterface
{
    public const ERR_INVALID_PERIOD = 'ICAL-TYPE-009';

    private DateTimeParser $dateTimeParser;

    public function __construct()
    {
        $this->dateTimeParser = new DateTimeParser();
    }

    public function parse(string $value, array $parameters = []): array
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty PERIOD value',
                self::ERR_INVALID_PERIOD
            );
        }

        $parts = explode('/', $value, 2);

        if (count($parts) !== 2) {
            throw new ParseException(
                'Invalid PERIOD format: missing slash separator: ' . $value,
                self::ERR_INVALID_PERIOD
            );
        }

        $startStr = $parts[0];
        $endStr = $parts[1];

        try {
            $start = $this->dateTimeParser->parse($startStr, $parameters);
        } catch (ParseException $e) {
            throw new ParseException(
                'Invalid PERIOD start: ' . $e->getMessage(),
                self::ERR_INVALID_PERIOD,
                $e->getContentLineNumber(),
                $e->getContentLine(),
                $e
            );
        }

        try {
            $end = $this->parsePeriodEnd($endStr, $parameters);
        } catch (ParseException $e) {
            throw new ParseException(
                'Invalid PERIOD end: ' . $e->getMessage(),
                self::ERR_INVALID_PERIOD,
                $e->getContentLineNumber(),
                $e->getContentLine(),
                $e
            );
        }

        return [$start, $end];
    }

    private function parsePeriodEnd(string $value, array $parameters): \DateTimeImmutable|\DateInterval
    {
        $value = trim($value);

        if (str_starts_with($value, 'P')) {
            $durationParser = new DurationParser();
            return $durationParser->parse($value, $parameters);
        }

        return $this->dateTimeParser->parse($value, $parameters);
    }

    public function getType(): string
    {
        return 'PERIOD';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        $parts = explode('/', $value, 2);

        if (count($parts) !== 2) {
            return false;
        }

        $startStr = trim($parts[0]);
        $endStr = trim($parts[1]);

        if (!$this->dateTimeParser->canParse($startStr)) {
            return false;
        }

        if ($this->dateTimeParser->canParse($endStr)) {
            return true;
        }

        $durationParser = new DurationParser();
        return $durationParser->canParse($endStr);
    }
}
