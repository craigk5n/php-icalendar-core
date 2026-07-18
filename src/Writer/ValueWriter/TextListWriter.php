<?php

declare(strict_types=1);

namespace Icalendar\Writer\ValueWriter;

/**
 * Writer for a comma-separated list of TEXT values (e.g. CATEGORIES, RESOURCES).
 *
 * RFC 5545 §3.8.1.2: `text *("," text)`. Each item is escaped as an individual
 * TEXT value (§3.3.11) and the items are joined with *literal* commas. Escaping
 * per item before joining is what keeps a comma inside a value (`\,`) distinct
 * from a list separator (`,`); joining first and escaping the whole string --
 * the old bug -- would escape the separators too and collapse the list.
 */
final class TextListWriter implements ValueWriterInterface
{
    private TextWriter $textWriter;

    public function __construct(?TextWriter $textWriter = null)
    {
        $this->textWriter = $textWriter ?? new TextWriter();
    }

    /**
     * @param mixed $value A list of item strings. A bare string is treated as a
     *                     single-item list; null/empty yields an empty value.
     */
    #[\Override]
    public function write(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException(
                'TextListWriter expects an array of strings, got ' . gettype($value)
            );
        }

        $escaped = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                throw new \InvalidArgumentException(
                    'TextListWriter expects scalar items, got ' . gettype($item)
                );
            }
            // Reuse TextWriter so per-item escaping and control-character
            // sanitisation stay identical to a standalone TEXT value.
            $escaped[] = $this->textWriter->write((string) $item);
        }

        return implode(',', $escaped);
    }

    #[\Override]
    public function getType(): string
    {
        return 'TEXT-LIST';
    }

    #[\Override]
    public function canWrite(mixed $value): bool
    {
        return is_array($value) || is_string($value) || is_null($value);
    }
}
