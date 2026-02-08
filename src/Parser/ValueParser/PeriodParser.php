<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for PERIOD values according to RFC 5545 §3.3.9
 */
class PeriodParser implements ValueParserInterface
{
    private bool $strict = false;

    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
        $this->dateTimeParser->setStrict($strict);
    }

    public const ERR_INVALID_PERIOD = 'ICAL-TYPE-009';

    private DateTimeParser $dateTimeParser;

    public function __construct()
    {
        $this->dateTimeParser = new DateTimeParser();
    }

    /**
     * @param array<string, string> $parameters
     * @return array<array-key, array{0: \DateTimeImmutable, 1: \DateTimeImmutable|\DateInterval}>
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): array
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException('Empty PERIOD value', self::ERR_INVALID_PERIOD);
        }

        // Handle multiple comma-separated periods
        $periodStrings = explode(',', $value);
        $periods = [];

        foreach ($periodStrings as $periodStr) {
            $periodStr = trim($periodStr);
            if ($periodStr === '') continue;

            $parts = explode('/', $periodStr, 2);

            if (count($parts) !== 2) {
                throw new ParseException('Invalid PERIOD format: missing slash separator: ' . $periodStr, self::ERR_INVALID_PERIOD);
            }

            $startStr = $parts[0];
            $endStr = $parts[1];

            try {
                $start = $this->dateTimeParser->parse($startStr, $parameters);
            } catch (ParseException $e) {
                throw new ParseException('Invalid PERIOD start: ' . $e->getMessage(), self::ERR_INVALID_PERIOD, $e->getContentLineNumber(), $e->getContentLine(), $e);
            }

            try {
                $end = $this->parsePeriodEnd($endStr, $parameters);
            } catch (ParseException $e) {
                throw new ParseException('Invalid PERIOD end: ' . $e->getMessage(), self::ERR_INVALID_PERIOD, $e->getContentLineNumber(), $e->getContentLine(), $e);
            }

            $periods[] = [$start, $end];
        }

        // If only one period was found, return it directly to match formatParsedValue expectations
        // if (count($periods) === 1) return $periods[0];
        // Actually, formatParsedValue handles nested arrays for multiple periods now.
        
        return $periods;
    }

    /**
     * @param array<string, string> $parameters
     */
    private function parsePeriodEnd(string $value, array $parameters): \DateTimeImmutable|\DateInterval
    {
        $value = trim($value);

        if (str_starts_with($value, 'P') || str_starts_with($value, '-P')) {
            $durationParser = new DurationParser();
            $durationParser->setStrict($this->strict);
            return $durationParser->parse($value, $parameters);
        }

        return $this->dateTimeParser->parse($value, $parameters);
    }

    #[\Override]
    public function getType(): string
    {
        return 'PERIOD';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        $value = trim($value);
        if ($value === '') return false;

        $periodStrings = explode(',', $value);
        foreach ($periodStrings as $periodStr) {
            $periodStr = trim($periodStr);
            if ($periodStr === '') continue;

            $parts = explode('/', $periodStr, 2);
            if (count($parts) !== 2) return false;

            $startStr = trim($parts[0]);
            $endStr = trim($parts[1]);

            if (!$this->dateTimeParser->canParse($startStr)) return false;

            $canParseEnd = false;
            if ($this->dateTimeParser->canParse($endStr)) {
                $canParseEnd = true;
            } else {
                $durationParser = new DurationParser();
                $durationParser->setStrict($this->strict);
                if ($durationParser->canParse($endStr)) {
                    $canParseEnd = true;
                }
            }
            
            if (!$canParseEnd) return false;
        }

        return true;
    }
}
