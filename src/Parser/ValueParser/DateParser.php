<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use DateTimeImmutable;
use DateTimeZone;
use Icalendar\Exception\ParseException;

/**
 * Parser for DATE values according to RFC 5545
 */
class DateParser implements ValueParserInterface
{
    /**
     * No-op: what counts as a DATE does not vary by mode.
     *
     * See DateTimeParser::setStrict() — this parser had the same
     * DateTimeImmutable fallback, with the same wall-clock dependency.
     */
    #[\Override]
    public function setStrict(bool $strict): void
    {
    }

    /**
     * @param array<string, string> $parameters
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): DateTimeImmutable
    {
        if (!preg_match('/^\d{8}$/', $value)) {
            throw new ParseException("Invalid DATE format: '{$value}'. Expected YYYYMMDD.", ParseException::ERR_INVALID_DATE);
        }

        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);

        if (!DateValidator::isValidDate($year, $month, $day)) {
            throw new ParseException("Invalid DATE value: '{$value}'", ParseException::ERR_INVALID_DATE);
        }

        $tzid = $parameters['TZID'] ?? '';
        $timezone = ($tzid !== '') ? new DateTimeZone($tzid) : new DateTimeZone('UTC');
        $dateString = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateString, $timezone);

        if ($date === false) {
            throw new ParseException("Invalid DATE value: '{$value}'", ParseException::ERR_INVALID_DATE);
        }

        return $date;
    }

    #[\Override]
    public function getType(): string
    {
        return 'DATE';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        if (!preg_match('/^\d{8}$/', $value)) {
            return false;
        }

        $year = (int) substr($value, 0, 4);
        $month = (int) substr($value, 4, 2);
        $day = (int) substr($value, 6, 2);

        return $year !== 0 && DateValidator::isValidDate($year, $month, $day);
    }
}
