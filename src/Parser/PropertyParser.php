<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Exception\ParseException;

/**
 * Parses property lines from iCalendar content according to RFC 5545
 *
 * Property format: name *(";" param) ":" value
 * where param = param-name "=" param-value *("," param-value)
 *
 * Supports:
 * - IANA tokens and X-names for property names
 * - Quoted parameter values (for values containing :, ;, ,)
 * - RFC 6868 parameter value encoding (^n, ^^, ^')
 * - Multi-valued comma-separated parameters
 */
class PropertyParser
{
    private ParameterParser $parameterParser;

    public function __construct()
    {
        $this->parameterParser = new ParameterParser();
    }
    /**
     * Parse a property line into a ContentLine object
     *
     * @param string $line The property line to parse
     * @param int $lineNumber Line number for error reporting (optional)
     * @param bool $strict True for strict RFC compliance
     * @throws ParseException if the line format is invalid
     */
    public function parse(string $line, int $lineNumber = 0, bool $strict = false): ContentLine
    {
        // Validate line contains a colon separator
        if (!str_contains($line, ':')) {
            throw new ParseException(
                'Invalid property format: missing colon separator',
                ParseException::ERR_INVALID_PROPERTY_FORMAT,
                $lineNumber,
                $line
            );
        }

        // Check for unclosed quotes before looking for colon
        $this->validateQuoteBalance($line, $lineNumber);

        // Find the first colon to separate name/params from value
        // Note: The value can contain colons, so we only split on the first one
        $colonPos = $this->findFirstUnquotedColon($line);
        if ($colonPos === false) {
            throw new ParseException(
                'Invalid property format: unable to find value separator',
                ParseException::ERR_INVALID_PROPERTY_FORMAT,
                $lineNumber,
                $line
            );
        }

        $nameAndParams = substr($line, 0, $colonPos);
        $value = substr($line, $colonPos + 1);

        // Parse property name and parameters
        $name = $this->parsePropertyName($nameAndParams, $line, $lineNumber, $strict);
        $parameters = $this->parseParameters($nameAndParams, $line, $lineNumber);

        return new ContentLine($line, $name, $parameters, $value, $lineNumber);
    }

    /**
     * Parse property name from the name and parameters portion
     *
     * @throws ParseException if property name is invalid
     */
    private function parsePropertyName(string $nameAndParams, string $rawLine, int $lineNumber, bool $strict): string
    {
        // Find the first semicolon (separator between name and parameters)
        $semicolonPos = strpos($nameAndParams, ';');

        if ($semicolonPos === false) {
            // No parameters, entire string is the name
            $name = $nameAndParams;
        } else {
            $name = substr($nameAndParams, 0, $semicolonPos);
        }

        // Validate property name is not empty
        if (empty($name)) {
            throw new ParseException(
                'Invalid property format: empty property name',
                ParseException::ERR_INVALID_PROPERTY_NAME,
                $lineNumber,
                $rawLine
            );
        }

        // Validate property name format (IANA token or X-name)
        if (!$this->isValidPropertyName($name, $strict)) {
            throw new ParseException(
                "Invalid property name: '{$name}'",
                ParseException::ERR_INVALID_PROPERTY_NAME,
                $lineNumber,
                $rawLine
            );
        }

        // Normalize to uppercase per RFC 5545 §1.3 (property names are case-insensitive)
        return strtoupper($name);
    }

    /**
     * Check if a property name is valid according to RFC 5545
     *
     * Valid formats:
     * - IANA token: alphanumeric and hyphen, must start with letter
     * - X-name: starts with "X-" or "x-" followed by vendor ID and name
     */
    private function isValidPropertyName(string $name, bool $strict): bool
    {
        // X-names start with X- or x-
        if (str_starts_with($name, 'X-') || str_starts_with($name, 'x-')) {
            return $this->isValidXName($name, $strict);
        }

        // IANA tokens: letters, digits, and hyphens
        // Must start with a letter
        if (!preg_match('/^[A-Za-z][A-Za-z0-9\-]*$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * Check if an X-name is valid according to RFC 5545
     *
     * Format: x-vendorid-propname
     * where vendorid is 1-8 alphanumeric characters
     */
    private function isValidXName(string $name, bool $strict): bool
    {
        // Must be at least "X-A" (X- + 1 char name)
        if (strlen($name) < 3) {
            return false;
        }

        // RFC 5545 §3.2 says X-names consist of "X-" followed by a name.
        // It recommends a vendor ID prefix (e.g., X-ABC-NAME), but many 
        // providers use non-prefixed names like X-WR-CALNAME.
        // We'll allow any X- followed by alphanumeric and hyphens.
        if (preg_match('/^[Xx]-[A-Za-z0-9\-]+$/', $name)) {
            return true;
        }

        return false;
    }

    /**
     * Parse parameters from the name and parameters portion
     *
     * @return array<string, string>
     * @throws ParseException if parameter format is invalid
     */
    private function parseParameters(string $nameAndParams, string $rawLine, int $lineNumber): array
    {
        // Find the first semicolon
        $semicolonPos = strpos($nameAndParams, ';');

        if ($semicolonPos === false) {
            // No parameters
            return [];
        }

        // Extract parameter string (everything after first semicolon)
        $paramString = substr($nameAndParams, $semicolonPos + 1);

        // Use the dedicated ParameterParser
        return $this->parameterParser->parse($paramString, $lineNumber, $rawLine);
    }

    /**
     * Find the first unquoted colon in a string
     *
     * The colon separates the name/params from the value, but colons
     * inside quoted parameter values should be ignored.
     *
     * @return int|false Position of colon or false if not found
     */
    private function findFirstUnquotedColon(string $line): int|false
    {
        $length = strlen($line);
        $inQuotes = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($char === '"') {
                $inQuotes = !$inQuotes;
            } elseif ($char === ':' && !$inQuotes) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Validate that all quotes in the line are properly balanced
     *
     * @throws ParseException if quotes are unbalanced or mismatched
     */
    private function validateQuoteBalance(string $line, int $lineNumber): void
    {
        $length = strlen($line);
        $inQuotes = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($char === '"') {
                $inQuotes = !$inQuotes;
            }
        }

        if ($inQuotes) {
            throw new ParseException(
                'Invalid parameter format: unclosed quoted string',
                ParseException::ERR_UNCLOSED_QUOTED_STRING,
                $lineNumber,
                $line
            );
        }
    }
}
