<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for URI values according to RFC 5545 §3.3.15
 */
class UriParser implements ValueParserInterface
{
    private bool $strict = false;

    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_URI = 'ICAL-TYPE-013';

    #[\Override]
    public function parse(string $value, array $parameters = []): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException('Empty URI value', self::ERR_INVALID_URI);
        }

        // RFC 3986 allows many types of URIs that FILTER_VALIDATE_URL rejects (like geo:, mailto:, data:)
        // For iCalendar, we should be more permissive.
        if ($this->strict && !preg_match('/^[a-z][a-z0-9+.-]*:.+/i', $value)) {
            throw new ParseException('Invalid URI format: ' . $value, self::ERR_INVALID_URI);
        }

        return $value;
    }

    #[\Override]
    public function getType(): string
    {
        return 'URI';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        $value = trim($value);
        if ($value === '') return false;
        if (!$this->strict) return true;
        return (bool) preg_match('/^[a-z][a-z0-9+.-]*:.+/i', $value);
    }
}