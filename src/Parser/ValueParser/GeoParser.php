<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for GEO values according to RFC 5545 §3.8.1.6.
 *
 * GEO is a structured value -- two ";"-separated FLOATs, `latitude ";" longitude`
 * (`GEO:37.386013;-122.082932`) -- not a single float, which is why it cannot be
 * mapped to FLOAT: FloatParser rejects the semicolon form outright. Without a
 * parser of its own GEO fell through to the TEXT default, and TextParser only
 * unescapes and never fails, so `GEO:total garbage` was accepted silently.
 *
 * Validates: exactly two components, both FLOAT, latitude in [-90, 90] and
 * longitude in [-180, 180]. FLOAT parsing is delegated to FloatParser so the two
 * halves accept exactly what a standalone FLOAT would (including the lenient
 * ".5" form). Returns the trimmed value unchanged, preserving the caller's
 * numeric formatting for a byte-stable round trip.
 */
class GeoParser implements ValueParserInterface
{
    private FloatParser $floatParser;

    public function __construct()
    {
        $this->floatParser = new FloatParser();
    }

    /**
     * GEO validation itself does not vary by mode; strictness only affects how
     * each FLOAT component is parsed, so it is delegated to FloatParser.
     */
    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->floatParser->setStrict($strict);
    }

    /**
     * @param array<string, string> $parameters
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): string
    {
        $trimmed = trim($value);

        $parts = explode(';', $trimmed);
        if (count($parts) !== 2) {
            throw new ParseException(
                "Invalid GEO value: expected 'latitude;longitude', got: {$value}",
                ParseException::ERR_INVALID_GEO
            );
        }

        [$latitude, $longitude] = $this->parseComponents($parts[0], $parts[1]);

        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new ParseException(
                "GEO latitude out of range [-90, 90]: {$parts[0]}",
                ParseException::ERR_INVALID_GEO
            );
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new ParseException(
                "GEO longitude out of range [-180, 180]: {$parts[1]}",
                ParseException::ERR_INVALID_GEO
            );
        }

        return $trimmed;
    }

    /**
     * @return array{float, float}
     */
    private function parseComponents(string $latitude, string $longitude): array
    {
        try {
            return [
                $this->floatParser->parse($latitude),
                $this->floatParser->parse($longitude),
            ];
        } catch (ParseException $e) {
            // Re-raise a FLOAT failure under the GEO code so callers see a GEO
            // error, not a stray FLOAT one, for a GEO property.
            throw new ParseException(
                "Invalid GEO value: component is not a FLOAT ({$e->getMessage()})",
                ParseException::ERR_INVALID_GEO,
                previous: $e
            );
        }
    }

    #[\Override]
    public function getType(): string
    {
        return 'GEO';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        try {
            $this->parse($value);
            return true;
        } catch (ParseException) {
            return false;
        }
    }
}
