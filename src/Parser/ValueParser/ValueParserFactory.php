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

        // Calendar address properties
        'ORGANIZER' => 'CAL-ADDRESS',
        'ATTENDEE' => 'CAL-ADDRESS',

        // Time properties
        'TZOFFSETFROM' => 'UTC-OFFSET',
        'TZOFFSETTO' => 'UTC-OFFSET',

        // Period properties
        'FREEBUSY' => 'PERIOD',
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
            return $this->getParser($parameters['VALUE']);
        }

        // Check for property default
        if (isset(self::$propertyDefaults[$propertyName])) {
            return $this->getParser(self::$propertyDefaults[$propertyName]);
        }

        // Default to TEXT
        return $this->getParser('TEXT');
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