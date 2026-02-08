<?php

declare(strict_types=1);

namespace Icalendar\Parser\ValueParser;

use Icalendar\Exception\ParseException;

/**
 * Parser for TEXT values according to RFC 5545
 */
class TextParser implements ValueParserInterface
{
    private bool $strict = false;

    #[\Override]
    public function setStrict(bool $strict): void
    {
        $this->strict = $strict;
    }

    /**
     * Parse a TEXT value, unescaping special characters
     *
     * @param string $value The escaped text value
     * @param array<string, string> $parameters Property parameters (unused for TEXT)
     * @return string The unescaped text
     * @throws ParseException if the escape sequence is invalid
     */
    #[\Override]
    public function parse(string $value, array $parameters = []): string
    {
        return $this->unescape($value);
    }

    #[\Override]
    public function getType(): string
    {
        return 'TEXT';
    }

    #[\Override]
    public function canParse(string $value): bool
    {
        if (str_ends_with($value, '\\') && !str_ends_with($value, '\\\\')) {
            return false;
        }
        return true;
    }

    private function unescape(string $value): string
    {
        $result = '';
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $nextChar = $value[$i + 1];

                switch ($nextChar) {
                    case '\\': $result .= '\\'; $i++; break;
                    case ';': $result .= ';'; $i++; break;
                    case ',': $result .= ','; $i++; break;
                    case 'n':
                    case 'N': $result .= "\n"; $i++; break;
                    default:
                        if ($this->strict) {
                            throw new ParseException("Invalid escape sequence: '\\{$nextChar}' in TEXT value", ParseException::ERR_INVALID_TEXT);
                        }
                        $result .= $nextChar;
                        $i++;
                }
            } elseif ($char === '\\' && $i + 1 >= $length) {
                if ($this->strict) {
                    throw new ParseException("Incomplete escape sequence at end of TEXT value", ParseException::ERR_INVALID_TEXT);
                }
                $result .= '\\';
            } else {
                $result .= $char;
            }
        }

        return $result;
    }
}