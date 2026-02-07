<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for CAL-ADDRESS values according to RFC 5545 §3.3.3
 *
 * CAL-ADDRESS is a URI with the mailto: scheme (or other schemes in practice).
 */
class CalAddressParser implements ValueParserInterface
{
    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    public const ERR_INVALID_CAL_ADDRESS = 'ICAL-TYPE-003';

    public function parse(string $value, array $parameters = []): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new ParseException(
                'Empty CAL-ADDRESS value',
                self::ERR_INVALID_CAL_ADDRESS
            );
        }

        $parsed = parse_url($value);

        if ($parsed === false || !isset($parsed['scheme'])) {
            throw new ParseException(
                'Invalid CAL-ADDRESS: must be a URI with scheme: ' . $value,
                self::ERR_INVALID_CAL_ADDRESS
            );
        }

        if ($parsed['scheme'] !== 'mailto') {
            throw new ParseException(
                'Invalid CAL-ADDRESS: scheme must be mailto: ' . $value,
                self::ERR_INVALID_CAL_ADDRESS
            );
        }

        if (!isset($parsed['path']) || empty($parsed['path'])) {
            throw new ParseException(
                'Invalid CAL-ADDRESS: missing email address: ' . $value,
                self::ERR_INVALID_CAL_ADDRESS
            );
        }

        // Validate email format - extract email from "Name <email>" format if present
        $email = $parsed['path'];
        if (preg_match('/<([^>]+)>/', $email, $matches)) {
            $email = $matches[1];
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ParseException(
                'Invalid CAL-ADDRESS: invalid email format: ' . $parsed['path'],
                self::ERR_INVALID_CAL_ADDRESS
            );
        }

        return $value;
    }

    public function getType(): string
    {
        return 'CAL-ADDRESS';
    }

    public function canParse(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        $parsed = parse_url($value);

        if ($parsed === false || !isset($parsed['scheme']) || $parsed['scheme'] !== 'mailto') {
            return false;
        }

        if (!isset($parsed['path']) || empty($parsed['path'])) {
            return false;
        }

        // Validate email format - extract email from "Name <email>" format if present
        $email = $parsed['path'];
        if (preg_match('/<([^>]+)>/', $email, $matches)) {
            $email = $matches[1];
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return true;
    }
}