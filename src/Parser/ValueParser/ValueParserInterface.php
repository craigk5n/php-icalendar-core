<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

/**
 * Interface for all value type parsers
 *
 * Value parsers are responsible for parsing iCalendar property values
 * according to RFC 5545 data type specifications.
 */
interface ValueParserInterface
{
    /**
     * Parse a raw property value into the appropriate PHP type
     *
     * @param string $value The raw value from the iCalendar data
     * @param array<string, string> $parameters Parameter values from the property
     * @return mixed The parsed value (type depends on implementation)
     * @throws \Icalendar\Exception\ParseException if the value cannot be parsed
     */
    /**
     * @param array<string, string> $parameters
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): mixed;

    /**
     * Get the data type name this parser handles
     *
     * @return string The RFC 5545 data type name (e.g., 'DATE', 'DATE-TIME', 'TEXT')
     */
    public function getType(): string;

    /**
     * Check if this parser can handle the given value
     *
     * This is used for validation and type detection.
     *
     * @param string $value The value to check
     * @return bool True if the value format is valid for this type
     */
    public function canParse(string $value): bool;

    /**
     * Set strict mode for this parser
     *
     * @param bool $strict True for strict RFC compliance, false for lenient parsing
     */
    public function setStrict(bool $strict): void;
}
