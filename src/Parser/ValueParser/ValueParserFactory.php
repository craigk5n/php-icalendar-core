<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Factory for creating and managing value type parsers
 *
 * Dispatches to appropriate parser based on VALUE parameter or property name.
 * Supports all RFC 5545 data types and caches parser instances for performance.
 */
class ValueParserFactory
{
    /** @var array<string, ValueParserInterface> */
    private array $parsers = [];

    private bool $strict = false;

    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
        // Update any already cached parsers
        foreach ($this->parsers as $parser) {
            $parser->setStrict($strict);
        }
    }

    /**
     * Properties whose VALUE parameter may declare a type other than the default
     *
     * Only properties offering a genuine choice are listed. Everything else in
     * $propertyDefaults permits its default and nothing more, and properties
     * absent from both are extensions whose types the library cannot know.
     *
     * @var array<string, list<string>>
     */
    private static array $propertyAlternateTypes = [
        'DTSTART' => ['DATE-TIME', 'DATE'],          // RFC 5545 §3.8.2.4
        'DTEND' => ['DATE-TIME', 'DATE'],            // RFC 5545 §3.8.2.2
        'DUE' => ['DATE-TIME', 'DATE'],              // RFC 5545 §3.8.2.3
        'RECURRENCE-ID' => ['DATE-TIME', 'DATE'],    // RFC 5545 §3.8.4.4
        'EXDATE' => ['DATE-TIME', 'DATE'],           // RFC 5545 §3.8.5.1
        'RDATE' => ['DATE-TIME', 'DATE', 'PERIOD'],  // RFC 5545 §3.8.5.2
        'TRIGGER' => ['DURATION', 'DATE-TIME'],      // RFC 5545 §3.8.6.3
        'ATTACH' => ['URI', 'BINARY'],               // RFC 5545 §3.8.1.1
        'IMAGE' => ['URI', 'BINARY'],                // RFC 7986 §5.10
        'STYLED-DESCRIPTION' => ['TEXT', 'URI'],     // RFC 9073 §6.5
    ];

    /** @var array<string, string> Default types for common properties */
    private static array $propertyDefaults = [
        // Date/Time properties
        'DTSTART' => 'DATE-TIME',
        'DTEND' => 'DATE-TIME',
        'DUE' => 'DATE-TIME',
        'DTSTAMP' => 'DATE-TIME',
        'CREATED' => 'DATE-TIME',
        'LAST-MODIFIED' => 'DATE-TIME',
        'COMPLETED' => 'DATE-TIME',
        'RECURRENCE-ID' => 'DATE-TIME',
        'EXDATE' => 'DATE-TIME',
        'RDATE' => 'DATE-TIME',
        'TRIGGER' => 'DURATION',

        // Recurrence properties (RFC 5545 §3.8.5.3, §3.3.10)
        'RRULE' => 'RECUR',
        'EXRULE' => 'RECUR',

        // Duration properties
        'DURATION' => 'DURATION',

        // Integer properties
        'SEQUENCE' => 'INTEGER',
        'PRIORITY' => 'INTEGER',
        'PERCENT-COMPLETE' => 'INTEGER',
        'REPEAT' => 'INTEGER',

        // Boolean properties
        'RSVP' => 'BOOLEAN',

        // URI properties
        'URL' => 'URI',
        // RFC 5545 §3.8.1.1: default type is URI; binary payloads declare VALUE=BINARY
        'ATTACH' => 'URI',

        // Text properties (default)
        'UID' => 'TEXT',
        'SUMMARY' => 'TEXT',
        'DESCRIPTION' => 'TEXT',
        'LOCATION' => 'TEXT',
        'COMMENT' => 'TEXT',
        'CONTACT' => 'TEXT',
        'TRANSP' => 'TEXT',
        'STATUS' => 'TEXT',
        'CLASS' => 'TEXT',
        'CATEGORIES' => 'TEXT',
        'RESOURCES' => 'TEXT',
        'ACTION' => 'TEXT',
        'TZID' => 'TEXT',
        'TZNAME' => 'TEXT',
        'METHOD' => 'TEXT',
        'PRODID' => 'TEXT',
        'VERSION' => 'TEXT',
        'CALSCALE' => 'TEXT',
        'BUSYTYPE' => 'TEXT',
        'PARTICIPANT-TYPE' => 'TEXT',

        // Calendar address properties
        'ORGANIZER' => 'CAL-ADDRESS',
        'ATTENDEE' => 'CAL-ADDRESS',

        // Time properties
        'TZOFFSETFROM' => 'UTC-OFFSET',
        'TZOFFSETTO' => 'UTC-OFFSET',

        // Period properties
        'FREEBUSY' => 'PERIOD',

        // Geographic position: a structured pair of FLOATs (RFC 5545 §3.8.1.6),
        // not a single FLOAT, so it needs its own parser rather than the FLOAT map.
        'GEO' => 'GEO',

        // Structured, semicolon-separated (RFC 5545 §3.8.8.3). As with GEO, the
        // separators are structural, so it must not be written as TEXT.
        'REQUEST-STATUS' => 'REQUEST-STATUS',

        // URIs. Absent from this map they inherited the TEXT default, which
        // cannot fail, so any value at all was accepted.
        'TZURL' => 'URI',
        'SOURCE' => 'URI',

        // Genuinely TEXT; mapped so the choice is deliberate rather than a
        // side effect of the fallback.
        'NAME' => 'TEXT',
        'RELATED-TO' => 'TEXT',

        // RFC 9073: Event Publishing Extensions
        // STYLED-DESCRIPTION can contain rich text (HTML) or URIs.
        // TEXT parser is suitable for capturing raw HTML/rich text.
        'STYLED-DESCRIPTION' => 'TEXT',

        // RFC 7986: New Properties for iCalendar
        'IMAGE' => 'URI',
        'COLOR' => 'TEXT',
        'CONFERENCE' => 'URI',
        'REFRESH-INTERVAL' => 'DURATION',
    ];

    /**
     * Get a parser for a specific data type
     *
     * @param string $type The RFC 5545 data type name
     * @return ValueParserInterface The parser for the specified type
     * @throws ParseException if the type is unknown
     */
    public function getParser(string $type): ValueParserInterface
    {
        $type = strtoupper($type);

        // Check if parser already cached
        if (isset($this->parsers[$type])) {
            return $this->parsers[$type];
        }

        // Create and cache new parser
        $parser = $this->createParser($type);
        $this->parsers[$type] = $parser;

        return $parser;
    }

    /**
     * Get the appropriate parser for a property
     *
     * Determines parser based on:
     * 1. VALUE parameter if present
     * 2. Default type for the property name
     * 3. Falls back to TEXT
     *
     * @param string $propertyName The property name (e.g., 'SUMMARY', 'DTSTART')
     * @param array<string, string> $parameters The property parameters
     * @return ValueParserInterface The appropriate parser
     */
    public function getParserForProperty(string $propertyName, array $parameters = []): ValueParserInterface
    {
        $propertyName = strtoupper($propertyName);

        // Check for VALUE parameter override
        if (isset($parameters['VALUE'])) {
            $declaredType = strtoupper($parameters['VALUE']);
            $allowedTypes = self::allowedValueTypes($propertyName);

            // A VALUE parameter may only redeclare a type the property permits.
            // Trusting it blindly let any property be re-typed to anything --
            // and VALUE=TEXT disabled validation outright, since TextParser
            // cannot fail. Unknown properties resolve to null: an extension's
            // value type is not ours to police.
            if ($allowedTypes !== null && !in_array($declaredType, $allowedTypes, true)) {
                throw new ParseException(
                    "Property {$propertyName} does not permit VALUE={$declaredType}; "
                    . 'expected one of: ' . implode(', ', $allowedTypes),
                    ParseException::ERR_TYPE_DECLARATION_MISMATCH
                );
            }

            return $this->getParser($declaredType);
        }

        // Check for property default
        if (isset(self::$propertyDefaults[$propertyName])) {
            return $this->getParser(self::$propertyDefaults[$propertyName]);
        }

        // Default to TEXT
        return $this->getParser('TEXT');
    }

    /**
     * Value types a property permits in its VALUE parameter
     *
     * Returns null for properties the library does not know -- X- and other
     * extensions -- which may declare any supported type.
     *
     * @param string $propertyName Uppercased property name
     * @return list<string>|null
     */
    private static function allowedValueTypes(string $propertyName): ?array
    {
        if (isset(self::$propertyAlternateTypes[$propertyName])) {
            return self::$propertyAlternateTypes[$propertyName];
        }

        // A known property with no listed alternates permits only its default.
        if (isset(self::$propertyDefaults[$propertyName])) {
            return [self::$propertyDefaults[$propertyName]];
        }

        return null;
    }

    /**
     * Parse a property value using the appropriate parser
     *
     * @param string $propertyName The property name
     * @param string $value The raw property value
     * @param array<string, string> $parameters The property parameters
     * @return mixed The parsed value
     * @throws ParseException if parsing fails
     */
    public function parseValue(string $propertyName, string $value, array $parameters = []): mixed
    {
        $parser = $this->getParserForProperty($propertyName, $parameters);
        return $parser->parse($value, $parameters);
    }

    /**
     * Check if a data type is supported
     *
     * @param string $type The RFC 5545 data type name
     * @return bool True if the type is supported
     */
    public function hasParser(string $type): bool
    {
        $type = strtoupper($type);
        return in_array($type, $this->getSupportedTypes(), true);
    }

    /**
     * Get all supported data types
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return [
            'BINARY',
            'BOOLEAN',
            'CAL-ADDRESS',
            'DATE',
            'DATE-TIME',
            'DURATION',
            'FLOAT',
            'GEO',
            'REQUEST-STATUS',
            'INTEGER',
            'PERIOD',
            'RECUR',
            'TEXT',
            'TIME',
            'URI',
            'UTC-OFFSET',
        ];
    }

    /**
     * Register a custom parser for a data type
     *
     * @param string $type The data type name
     * @param ValueParserInterface $parser The parser instance
     */
    public function registerParser(string $type, ValueParserInterface $parser): void
    {
        $this->parsers[strtoupper($type)] = $parser;
    }

    /**
     * Clear all cached parsers
     */
    public function clearCache(): void
    {
        $this->parsers = [];
    }

    /**
     * Create a parser instance for a data type
     *
     * @param string $type The RFC 5545 data type name
     * @return ValueParserInterface The parser instance
     * @throws ParseException if the type is unknown
     */
    private function createParser(string $type): ValueParserInterface
    {
        $parser = match ($type) {
            'TEXT' => new TextParser(),
            'DATE' => new DateParser(),
            'DATE-TIME' => new DateTimeParser(),
            'DURATION' => new DurationParser(),
            'INTEGER' => new IntegerParser(),
            'FLOAT' => new FloatParser(),
            'BOOLEAN' => new BooleanParser(),
            'URI' => new UriParser(),
            'CAL-ADDRESS' => new CalAddressParser(),
            'BINARY' => new BinaryParser(),
            'PERIOD' => new PeriodParser(),
            'TIME' => new TimeParser(),
            'UTC-OFFSET' => new UtcOffsetParser(),
            'RECUR' => new RecurParser(),
            'GEO' => new GeoParser(),
            'REQUEST-STATUS' => new RequestStatusParser(),
            default => throw new ParseException(
                "Unknown data type: '{$type}'",
                ParseException::ERR_TYPE_DECLARATION_MISMATCH,
                0,
                null
            ),
        };

        $parser->setStrict($this->strict);
        return $parser;
    }
}