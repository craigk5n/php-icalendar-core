<?php

declare(strict_types=1);

namespace Icalendar\Value;

/**
 * A comma-separated list of TEXT values (e.g. CATEGORIES, RESOURCES).
 *
 * RFC 5545 §3.8.1.2 defines CATEGORIES as `text *("," text)`: the separator
 * commas are literal and only a comma *inside* an individual value is escaped
 * (§3.3.11). Holding the items as a list -- rather than a single pre-joined
 * TEXT value -- keeps that distinction intact all the way to the writer, which
 * escapes each item and joins them with literal commas. Storing them joined
 * would let TextWriter escape the separators too, collapsing the list into one
 * value against a strict parser.
 */
final class TextListValue implements ValueInterface
{
    /** @var list<string> Unescaped item values */
    private array $items;

    /**
     * @param array<int, string> $items Unescaped item values
     */
    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * Build from a raw wire value, splitting on unescaped commas and unescaping
     * each item. Symmetric with how the writer serialises the list.
     */
    public static function fromRawValue(string $wire): self
    {
        if ($wire === '') {
            return new self([]);
        }

        $items = [];
        foreach (self::splitOnUnescapedCommas($wire) as $escapedItem) {
            $items[] = self::unescape($escapedItem);
        }

        return new self($items);
    }

    /**
     * Split a wire value on its list-separator commas only, leaving escaped
     * commas (`\,`) inside their item untouched.
     *
     * @return list<string> The still-escaped items
     */
    public static function splitOnUnescapedCommas(string $wire): array
    {
        return self::splitOnUnescaped($wire, ',');
    }

    /**
     * As above, for the semicolon-separated structured values such as
     * REQUEST-STATUS (RFC 5545 §3.8.8.3).
     *
     * @return list<string> The still-escaped components
     */
    public static function splitOnUnescapedSemicolons(string $wire): array
    {
        return self::splitOnUnescaped($wire, ';');
    }

    /**
     * Split on a delimiter, ignoring any occurrence guarded by a backslash.
     *
     * @param non-empty-string $delimiter
     * @return list<string> The still-escaped components
     */
    private static function splitOnUnescaped(string $wire, string $delimiter): array
    {
        $items = [];
        $current = '';
        $length = strlen($wire);

        for ($i = 0; $i < $length; $i++) {
            $char = $wire[$i];

            if ($char === '\\' && $i + 1 < $length) {
                // Preserve the escape pair verbatim; the delimiter it may guard
                // is literal, not a separator.
                $current .= $char . $wire[$i + 1];
                $i++;
                continue;
            }

            if ($char === $delimiter) {
                $items[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $items[] = $current;

        return $items;
    }

    /**
     * @return list<string> The unescaped item values
     */
    public function getItems(): array
    {
        return $this->items;
    }

    #[\Override]
    public function getType(): string
    {
        return 'TEXT-LIST';
    }

    /**
     * The unescaped items joined by commas. This is a human-readable view; the
     * wire form (with per-item escaping) is produced by TextListWriter, which
     * receives the items directly, not this string.
     */
    #[\Override]
    public function getRawValue(): string
    {
        return implode(',', $this->items);
    }

    #[\Override]
    public function serialize(): string
    {
        return $this->getRawValue();
    }

    #[\Override]
    public function isDefault(): bool
    {
        return false;
    }

    /**
     * Unescape a single TEXT item per RFC 5545 §3.3.11.
     */
    private static function unescape(string $value): string
    {
        $result = '';
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $next = $value[$i + 1];
                switch ($next) {
                    case '\\':
                    case ';':
                    case ',':
                        $result .= $next;
                        $i++;
                        break;
                    case 'n':
                    case 'N':
                        $result .= "\n";
                        $i++;
                        break;
                    default:
                        $result .= $next;
                        $i++;
                }
                continue;
            }

            $result .= $char;
        }

        return $result;
    }
}
