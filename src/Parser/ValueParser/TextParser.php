<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for TEXT values according to RFC 5545
 *
 * TEXT values may contain escaped characters:
 * - \\ -> backslash
 * - \; -> semicolon
 * - \, -> comma
 * - \n or \N -> newline (line feed)
 *
 * Example: "Meeting\\, Lunch at Joe's" unescapes to "Meeting, Lunch at Joe's"
 */
class TextParser implements ValueParserInterface
{
    /**
     * Parse a TEXT value, unescaping special characters
     *
     * @param string $value The escaped text value
     * @param array<string, string> $parameters Property parameters (unused for TEXT)
     * @return string The unescaped text
     * @throws ParseException if the escape sequence is invalid
     */
    public function parse(string $value, array $parameters = []): string
    {
        return $this->unescape($value);
    }

    /**
     * Get the data type name
     */
    public function getType(): string
    {
        return 'TEXT';
    }

    /**
     * Check if the value is a valid TEXT format
     *
     * TEXT can be any string, but we validate that escape sequences are complete.
     */
    public function canParse(string $value): bool
    {
        // Check for incomplete escape sequences
        // A backslash at the end of the string would be incomplete
        if (str_ends_with($value, '\\') && !str_ends_with($value, '\\\\')) {
            return false;
        }

        return true;
    }

    /**
     * Unescape special characters in the text
     *
     * RFC 5545 defines these escape sequences:
     * - \\  -> backslash
     * - \;  -> semicolon
     * - \,  -> comma
     * - \n  -> newline (LF)
     * - \N  -> newline (LF)
     *
     * @throws ParseException if an invalid escape sequence is encountered
     */
    private function unescape(string $value): string
    {
        $result = '';
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $nextChar = $value[$i + 1];

                switch ($nextChar) {
                    case '\\':
                        $result .= '\\';
                        $i++; // Skip the escaped backslash
                        break;
                    case ';':
                        $result .= ';';
                        $i++; // Skip the escaped semicolon
                        break;
                    case ',':
                        $result .= ',';
                        $i++; // Skip the escaped comma
                        break;
                    case 'n':
                    case 'N':
                        $result .= "\n";
                        $i++; // Skip the 'n' or 'N'
                        break;
                    default:
                        // Invalid escape sequence
                        throw new ParseException(
                            "Invalid escape sequence: '\\{$nextChar}' in TEXT value",
                            ParseException::ERR_INVALID_TEXT
                        );
                }
            } elseif ($char === '\\' && $i + 1 >= $length) {
                // Trailing backslash without escape character
                throw new ParseException(
                    "Incomplete escape sequence at end of TEXT value",
                    ParseException::ERR_INVALID_TEXT
                );
            } else {
                $result .= $char;
            }
        }

        return $result;
    }
}
