<?php

declare(strict_types=1);

namespace Icalendar\Parser;

use Icalendar\Exception\ParseException;

/**
 * Parses parameter strings from iCalendar content according to RFC 5545
 *
 * Parameter format: param-name "=" param-value *("," param-value)
 *
 * Supports:
 * - Quoted parameter values (for values containing :, ;, ,)
 * - RFC 6868 parameter value encoding (^n, ^^, ^')
 * - Multi-valued comma-separated parameters
 * - Parameters without values (treated as empty string)
 */
class ParameterParser
{
    /**
     * Parse a parameter string into an associative array
     *
     * Parameter string format: "param1=value1;param2=value2;..."
     *
     * @param string $paramString The parameter string to parse (without property name)
     * @param int $lineNumber Line number for error reporting (optional)
     * @param string|null $rawLine The original line for error context (optional)
     * @return array<string, string> Associative array of parameter name => value
     * @throws ParseException if the parameter format is invalid
     */
    public function parse(string $paramString, int $lineNumber = 0, ?string $rawLine = null): array
    {
        if (empty($paramString)) {
            return [];
        }

        $parameters = [];
        $paramPairs = $this->splitParameters($paramString, $lineNumber, $rawLine);

        foreach ($paramPairs as $paramPair) {
            if (empty($paramPair)) {
                continue;
            }

            $param = $this->parseParameter($paramPair, $lineNumber, $rawLine);
            if ($param !== null) {
                $parameters[$param['name']] = $param['value'];
            }
        }

        return $parameters;
    }

    /**
     * Parse a single parameter pair (name=value)
     *
     * @return array{name: string, value: string}|null
     * @throws ParseException if parameter format is invalid
     */
    private function parseParameter(string $paramPair, int $lineNumber, ?string $rawLine): ?array
    {
        $eqPos = strpos($paramPair, '=');

        if ($eqPos === false) {
            // Parameter without value - per RFC this is allowed but unusual
            // We treat it as parameter with empty value
            // Normalize to uppercase per RFC 5545 §1.3
            return [
                'name' => strtoupper($paramPair),
                'value' => ''
            ];
        }

        $name = substr($paramPair, 0, $eqPos);
        $value = substr($paramPair, $eqPos + 1);

        // Validate parameter name
        if (empty($name)) {
            throw new ParseException(
                'Invalid parameter format: empty parameter name',
                ParseException::ERR_INVALID_PARAMETER_FORMAT,
                $lineNumber,
                $rawLine
            );
        }

        // Validate parameter name format (IANA token)
        if (!$this->isValidParameterName($name)) {
            throw new ParseException(
                "Invalid parameter name: '{$name}'",
                ParseException::ERR_INVALID_PARAMETER_FORMAT,
                $lineNumber,
                $rawLine
            );
        }

        // Parse parameter value (handles quoted strings and multi-values)
        $value = $this->parseParameterValue($value, $lineNumber, $rawLine);

        // Normalize parameter name to uppercase per RFC 5545 §1.3 (parameter names are case-insensitive)
        return [
            'name' => strtoupper($name),
            'value' => $value
        ];
    }

    /**
     * Check if a parameter name is valid according to RFC 5545
     *
     * Parameter names must be valid IANA tokens:
     * - Letters, digits, and hyphens
     * - Must start with a letter
     */
    private function isValidParameterName(string $name): bool
    {
        // IANA tokens: letters, digits, and hyphens
        // Must start with a letter
        return preg_match('/^[A-Za-z][A-Za-z0-9\-]*$/', $name) === 1;
    }

    /**
     * Split parameter string into individual parameter pairs
     *
     * Handles quoted values that may contain semicolons
     *
     * @return array<string>
     * @throws ParseException if unclosed quotes are detected
     */
    private function splitParameters(string $paramString, int $lineNumber, ?string $rawLine): array
    {
        $params = [];
        $current = '';
        $inQuotes = false;
        $length = strlen($paramString);

        for ($i = 0; $i < $length; $i++) {
            $char = $paramString[$i];

            if ($char === '"') {
                $inQuotes = !$inQuotes;
                $current .= $char;
            } elseif ($char === ';' && !$inQuotes) {
                // End of current parameter
                if (!empty($current)) {
                    $params[] = $current;
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        // Don't forget the last parameter
        if (!empty($current)) {
            $params[] = $current;
        }

        // Check for unclosed quotes
        if ($inQuotes) {
            throw new ParseException(
                'Invalid parameter format: unclosed quoted string',
                ParseException::ERR_UNCLOSED_QUOTED_STRING,
                $lineNumber,
                $rawLine
            );
        }

        return $params;
    }

    /**
     * Parse parameter value, handling quoted strings and multi-values
     *
     * @throws ParseException if value format is invalid
     */
    private function parseParameterValue(string $value, int $lineNumber, ?string $rawLine): string
    {
        // Handle multi-valued parameters (comma-separated values, each potentially quoted)
        if (str_contains($value, ',')) {
            return $this->parseMultiValuedParameter($value, $lineNumber, $rawLine);
        }

        return $this->parseSingleParameterValue($value, $lineNumber, $rawLine);
    }

    /**
     * Parse a multi-valued parameter with comma-separated values
     *
     * Each value may be quoted and may contain RFC 6868 encoded characters.
     *
     * @throws ParseException if value format is invalid
     */
    private function parseMultiValuedParameter(string $value, int $lineNumber, ?string $rawLine): string
    {
        $values = [];
        $currentValue = '';
        $inQuotes = false;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($char === '"') {
                $inQuotes = !$inQuotes;
                $currentValue .= $char;
            } elseif ($char === ',' && !$inQuotes) {
                // End of current value
                $values[] = $this->parseSingleParameterValue(trim($currentValue), $lineNumber, $rawLine);
                $currentValue = '';
            } else {
                $currentValue .= $char;
            }
        }

        // Don't forget the last value
        if (!empty($currentValue)) {
            $values[] = $this->parseSingleParameterValue(trim($currentValue), $lineNumber, $rawLine);
        }

        return implode(',', $values);
    }

    /**
     * Parse a single parameter value (unquoted and decode RFC 6868)
     */
    private function parseSingleParameterValue(string $value, int $lineNumber, ?string $rawLine): string
    {
        // Handle quoted string
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
            $value = $this->decodeRfc6868($value, $lineNumber, $rawLine);
        }

        return $value;
    }

    /**
     * Decode RFC 6868 caret-encoded sequences
     *
     * ^n -> newline (LF or CRLF)
     * ^^ -> caret (^)
     * ^' -> double quote (")
     *
     * @throws ParseException if encoding is invalid
     */
    private function decodeRfc6868(string $value, int $lineNumber, ?string $rawLine): string
    {
        $result = '';
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($char === '^' && $i + 1 < $length) {
                $nextChar = $value[$i + 1];

                switch ($nextChar) {
                    case 'n':
                    case 'N':
                        $result .= "\n";
                        $i++; // Skip the 'n'
                        break;
                    case '^':
                        $result .= '^';
                        $i++; // Skip the second '^'
                        break;
                    case "'":
                        $result .= '"';
                        $i++; // Skip the quote
                        break;
                    default:
                        // Invalid sequence - per RFC we could ignore or throw
                        // For strict parsing, throw an error
                        throw new ParseException(
                            "Invalid RFC 6868 encoding: '^{$nextChar}'",
                            ParseException::ERR_INVALID_RFC6868_ENCODING,
                            $lineNumber,
                            $rawLine
                        );
                }
            } else {
                $result .= $char;
            }
        }

        return $result;
    }
}
